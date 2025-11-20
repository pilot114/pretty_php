<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\Num;

#[BeforeMethods('setUp')]
class NumBench
{
    private int $intValue;

    private float $floatValue;

    private Num $intNum;

    private Num $floatNum;

    public function setUp(): void
    {
        $this->intValue = 42;
        $this->floatValue = 3.14159;

        $this->intNum = new Num($this->intValue);
        $this->floatNum = new Num($this->floatValue);
    }

    // ==================== Formatting ====================

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNumFormat(): void
    {
        $this->floatNum->format(2);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeFormat(): void
    {
        number_format($this->floatValue, 2);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNumCurrency(): void
    {
        $this->floatNum->currency();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeCurrency(): void
    {
        '$' . number_format($this->floatValue, 2);
    }

    // ==================== Math Operations ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumRound(): void
    {
        $this->floatNum->round(2);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeRound(): void
    {
        round($this->floatValue, 2);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumFloor(): void
    {
        $this->floatNum->floor();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeFloor(): void
    {
        floor($this->floatValue);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumCeil(): void
    {
        $this->floatNum->ceil();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeCeil(): void
    {
        ceil($this->floatValue);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumAbs(): void
    {
        $this->intNum->abs();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeAbs(): void
    {
        abs($this->intValue);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumClamp(): void
    {
        $this->intNum->clamp(0, 100);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeClamp(): void
    {
        max(0, min(100, $this->intValue));
    }

    // ==================== Arithmetic ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumAdd(): void
    {
        $this->intNum->add(10);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeAdd(): void
    {
        $this->intValue + 10;
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumMultiply(): void
    {
        $this->intNum->multiply(2);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeMultiply(): void
    {
        $this->intValue * 2;
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumDivide(): void
    {
        $this->intNum->divide(2);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeDivide(): void
    {
        $this->intValue / 2;
    }

    // ==================== Validation ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumIsEven(): void
    {
        $this->intNum->isEven();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeIsEven(): void
    {
        $this->intValue % 2 === 0;
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNumIsPrime(): void
    {
        $this->intNum->isPrime();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeIsPrime(): void
    {
        $value = $this->intValue;
        if ($value < 2) {
            $isPrime = false;
        } elseif ($value === 2) {
            $isPrime = true;
        } elseif ($value % 2 === 0) {
            $isPrime = false;
        } else {
            $isPrime = true;
            $sqrt = (int) sqrt($value);
            for ($i = 3; $i <= $sqrt; $i += 2) {
                if ($value % $i === 0) {
                    $isPrime = false;
                    break;
                }
            }
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumInRange(): void
    {
        $this->intNum->inRange(0, 100);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeInRange(): void
    {
        $this->intValue >= 0 && $this->intValue <= 100;
    }

    // ==================== Base Conversion ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumToHex(): void
    {
        $this->intNum->toHex();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeToHex(): void
    {
        dechex($this->intValue);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumToBinary(): void
    {
        $this->intNum->toBinary();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeToBinary(): void
    {
        decbin($this->intValue);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumFromHex(): void
    {
        Num::fromHex('2a');
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeFromHex(): void
    {
        hexdec('2a');
    }

    // ==================== Comparison ====================

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumGreaterThan(): void
    {
        $this->intNum->greaterThan(10);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeGreaterThan(): void
    {
        $this->intValue > 10;
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNumMax(): void
    {
        $this->intNum->max(100);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeMax(): void
    {
        max($this->intValue, 100);
    }
}
