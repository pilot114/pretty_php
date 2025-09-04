<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\Arr;

#[BeforeMethods('setUp')]
class ArrBench
{
    private array $smallArray;

    private array $largeArray;

    private Arr $smallArr;

    private Arr $largeArr;

    public function setUp(): void
    {
        // Small array (100 elements)
        $this->smallArray = range(1, 100);
        $this->smallArr = new Arr($this->smallArray);

        // Large array (10,000 elements)
        $this->largeArray = range(1, 10000);
        $this->largeArr = new Arr($this->largeArray);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchArrMapSmall(): void
    {
        $this->smallArr->map(fn($x): int|float => $x * 2);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeMapSmall(): void
    {
        array_map(fn($x): int|float => $x * 2, $this->smallArray);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchArrMapLarge(): void
    {
        $this->largeArr->map(fn($x): int|float => $x * 2);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchNativeMapLarge(): void
    {
        array_map(fn($x): int|float => $x * 2, $this->largeArray);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchArrFilterSmall(): void
    {
        $this->smallArr->filter(fn($x): bool => $x % 2 === 0);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeFilterSmall(): void
    {
        array_filter($this->smallArray, fn($x): bool => $x % 2 === 0);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchArrFilterLarge(): void
    {
        $this->largeArr->filter(fn($x): bool => $x % 2 === 0);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchNativeFilterLarge(): void
    {
        array_filter($this->largeArray, fn($x): bool => $x % 2 === 0);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchArrReduceSmall(): void
    {
        $this->smallArr->reduce(fn($carry, $item): float|int|array => $carry + $item, 0);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeReduceSmall(): void
    {
        array_reduce($this->smallArray, fn($carry, $item): float|int|array => $carry + $item, 0);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchArrReduceLarge(): void
    {
        $this->largeArr->reduce(fn($carry, $item): float|int|array => $carry + $item, 0);
    }

    #[Revs(100)]
    #[Iterations(10)]
    public function benchNativeReduceLarge(): void
    {
        array_reduce($this->largeArray, fn($carry, $item): float|int|array => $carry + $item, 0);
    }
}
