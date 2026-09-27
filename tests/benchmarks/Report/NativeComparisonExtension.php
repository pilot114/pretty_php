<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks\Report;

use PhpBench\DependencyInjection\Container;
use PhpBench\DependencyInjection\ExtensionInterface;
use PhpBench\Extension\ReportExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Registers the `native_comparison` report generator (enabled in phpbench.json via `core.extensions`).
 */
final class NativeComparisonExtension implements ExtensionInterface
{
    public function load(Container $container): void
    {
        $container->register(
            NativeComparisonGenerator::class,
            static fn (): NativeComparisonGenerator => new NativeComparisonGenerator(),
            [ReportExtension::TAG_REPORT_GENERATOR => ['name' => 'native_comparison']],
        );
    }

    public function configure(OptionsResolver $resolver): void
    {
    }
}
