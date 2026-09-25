<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\DateTime;

#[BeforeMethods('setUp')]
class DateTimeBench
{
    private DateTime $dateTime;

    private \DateTimeImmutable $native;

    public function setUp(): void
    {
        $this->dateTime = new DateTime('2024-01-15 10:30:00', 'UTC');
        $this->native = new \DateTimeImmutable('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
    }

    // ==================== Parsing ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchParse(): void
    {
        DateTime::parse('2024-01-15 10:30:00', 'UTC');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeParse(): void
    {
        new \DateTimeImmutable('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
    }

    // ==================== Formatting ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchFormat(): void
    {
        $this->dateTime->format('Y-m-d H:i:s');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeFormat(): void
    {
        $this->native->format('Y-m-d H:i:s');
    }

    // ==================== Arithmetic ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchAddDaysChain(): void
    {
        $this->dateTime->addDays(1)->addHours(2)->startOfDay();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeModifyChain(): void
    {
        $this->native->modify('+1 day')->modify('+2 hours')->setTime(0, 0);
    }
}
