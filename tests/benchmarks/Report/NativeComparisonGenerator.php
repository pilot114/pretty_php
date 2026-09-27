<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks\Report;

use PhpBench\Expression\Ast\PercentDifferenceNode;
use PhpBench\Model\Benchmark;
use PhpBench\Model\Subject;
use PhpBench\Model\SuiteCollection;
use PhpBench\Model\Variant;
use PhpBench\Registry\Config;
use PhpBench\Report\GeneratorInterface;
use PhpBench\Report\Model\Builder\ReportBuilder;
use PhpBench\Report\Model\Builder\TableBuilder;
use PhpBench\Report\Model\Reports;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Report that puts every Pretty PHP subject next to its native PHP counterpart.
 *
 * Subjects are paired by name inside a benchmark class: `benchNativeX` is the reference for `benchX`
 * or `bench<Class>X` (e.g. `benchStrUpperShort` in `StrBench`). Times are the mode of the per-revolution
 * time. With `--ref`, an extra column shows how the Pretty PHP subject moved against the baseline run.
 */
final class NativeComparisonGenerator implements GeneratorInterface
{
    private const string NATIVE_PREFIX = 'benchNative';

    private const int BAR_WIDTH = 20;

    /** Ratio at which the bar is full: log scale, ×1 → empty, ×32 and more → full. */
    private const float BAR_MAX_RATIO = 32.0;

    public function configure(OptionsResolver $options): void
    {
    }

    public function generate(SuiteCollection $collection, Config $config): Reports
    {
        $suite = $collection->first();
        $hasBaseline = $suite->getBaseline() instanceof \PhpBench\Model\Suite;
        $builder = ReportBuilder::create('Pretty PHP vs native PHP')
            ->withDescription('Mode time per call. Overhead = Pretty PHP − native; ratio = Pretty PHP / native.');
        $ratios = [];

        $benchmarks = $suite->getBenchmarks();
        usort($benchmarks, static fn (Benchmark $a, Benchmark $b): int => $a->getClass() <=> $b->getClass());

        foreach ($benchmarks as $benchmark) {
            $table = TableBuilder::create()->withTitle($this->benchmarkLabel($benchmark));

            foreach ($this->pairs($benchmark) as $operation => [$pretty, $native]) {
                $prettyTime = $this->time($pretty);
                $nativeTime = $this->time($native);
                $row = [
                    'operation' => $operation,
                    'native' => $this->formatTime($nativeTime),
                    'pretty php' => $this->formatTime($prettyTime),
                    'overhead' => '—',
                    'ratio' => '—',
                    'slowdown (log scale)' => '',
                ];

                if ($prettyTime !== null && $nativeTime !== null && $nativeTime > 0.0) {
                    $ratio = $prettyTime / $nativeTime;
                    $ratios[] = $ratio;
                    $row['overhead'] = $this->formatDelta($prettyTime - $nativeTime);
                    $row['ratio'] = $this->colorize(sprintf('×%.2f', $ratio), $ratio);
                    $row['slowdown (log scale)'] = $this->colorize($this->bar($ratio), $ratio);
                }

                if ($hasBaseline) {
                    $baselineTime = $pretty instanceof Variant ? $this->time($pretty->getBaseline()) : null;
                    $row['vs ref'] = $prettyTime !== null && $baselineTime !== null && $baselineTime > 0.0
                        ? new PercentDifferenceNode(($prettyTime - $baselineTime) / $baselineTime * 100)
                        : '—';
                }

                $table->addRowArray($row);
            }

            $builder->addObject($table->build());
        }

        if ($ratios !== []) {
            $builder->addObject($this->summary($ratios));
        }

        return Reports::fromReport($builder->build());
    }

    /**
     * Pretty PHP / native variant pairs keyed by operation name, in declaration order.
     *
     * @return array<string, array{?Variant, ?Variant}>
     */
    private function pairs(Benchmark $benchmark): array
    {
        $prefix = 'bench' . $this->benchmarkLabel($benchmark);
        $natives = [];
        $pretty = [];

        foreach ($benchmark->getSubjects() as $subject) {
            $name = $subject->getName();
            if (str_starts_with($name, self::NATIVE_PREFIX)) {
                $natives[substr($name, strlen(self::NATIVE_PREFIX))] = $this->variant($subject);
                continue;
            }

            $operation = str_starts_with($name, $prefix) && strlen($name) > strlen($prefix)
                ? substr($name, strlen($prefix))
                : substr($name, strlen('bench'));
            $pretty[$operation] = $this->variant($subject);
        }

        $pairs = [];
        foreach ($pretty as $operation => $variant) {
            $pairs[$operation] = [$variant, $natives[$operation] ?? null];
            unset($natives[$operation]);
        }

        foreach ($natives as $operation => $variant) {
            $pairs[$operation] = [null, $variant];
        }

        return $pairs;
    }

    private function benchmarkLabel(Benchmark $benchmark): string
    {
        $class = $benchmark->getClass();
        $short = substr($class, (int) strrpos($class, '\\') + 1);

        return str_ends_with($short, 'Bench') ? substr($short, 0, -strlen('Bench')) : $short;
    }

    private function variant(Subject $subject): ?Variant
    {
        return array_first($subject->getVariants());
    }

    /** Mode of the per-revolution time in microseconds, null when the variant is missing or failed. */
    private function time(?Variant $variant): ?float
    {
        if (!$variant instanceof \PhpBench\Model\Variant || $variant->hasErrorStack() || !$variant->isComputed()) {
            return null;
        }

        return (float) $variant->getStats()->getMode();
    }

    private function formatTime(?float $microseconds): string
    {
        return $microseconds === null ? '—' : $this->humanTime($microseconds);
    }

    private function formatDelta(float $microseconds): string
    {
        return ($microseconds < 0 ? '−' : '+') . $this->humanTime(abs($microseconds));
    }

    private function humanTime(float $microseconds): string
    {
        return match (true) {
            $microseconds < 1.0 => sprintf('%.0f ns', $microseconds * 1000),
            $microseconds < 1000.0 => sprintf('%.2f μs', $microseconds),
            default => sprintf('%.2f ms', $microseconds / 1000),
        };
    }

    /** Log-scale bar of the slowdown: each quarter of the width is one doubling (×2, ×4, ×8, ...). */
    private function bar(float $ratio): string
    {
        if ($ratio <= 1.0) {
            return '';
        }

        $filled = log(min($ratio, self::BAR_MAX_RATIO)) / log(self::BAR_MAX_RATIO) * self::BAR_WIDTH;
        $cells = max(1, (int) round($filled));

        return str_repeat('█', $cells) . ($ratio > self::BAR_MAX_RATIO ? '▶' : '');
    }

    /** Green: within 10% of native, yellow: up to twice as slow, red: slower. */
    private function colorize(string $text, float $ratio): string
    {
        if ($text === '') {
            return $text;
        }

        $color = match (true) {
            $ratio <= 1.1 => 'green',
            $ratio <= 2.0 => 'yellow',
            default => 'red',
        };

        return sprintf('<fg=%s>%s</>', $color, $text);
    }

    /**
     * @param non-empty-list<float> $ratios
     */
    private function summary(array $ratios): \PhpBench\Report\Model\Table
    {
        sort($ratios);
        $count = count($ratios);
        $middle = intdiv($count, 2);
        $median = $count % 2 === 1 ? $ratios[$middle] : ($ratios[$middle - 1] + $ratios[$middle]) / 2;
        $geomean = exp(array_sum(array_map(log(...), $ratios)) / $count);
        $within = count(array_filter($ratios, static fn (float $ratio): bool => $ratio <= 1.1));

        return TableBuilder::create()
            ->withTitle('Summary')
            ->addRowArray([
                'pairs' => $count,
                'within 10% of native' => $within,
                'median ratio' => $this->colorize(sprintf('×%.2f', $median), $median),
                'geometric mean' => $this->colorize(sprintf('×%.2f', $geomean), $geomean),
                'best' => $this->colorize(sprintf('×%.2f', $ratios[0]), $ratios[0]),
                'worst' => $this->colorize(sprintf('×%.2f', $ratios[$count - 1]), $ratios[$count - 1]),
            ])
            ->build();
    }
}
