<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\Str;

/**
 * Str is UTF-8 aware, so length and case conversion are compared with the mb_* functions that give the same
 * result, not with the byte-wise strlen()/strtoupper()/strtolower().
 */
#[BeforeMethods('setUp')]
class StrBench
{
    private string $shortString;

    private string $longString;

    private Str $shortStr;

    private Str $longStr;

    public function setUp(): void
    {
        $this->shortString = 'Hello World!';
        $this->longString = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100);

        $this->shortStr = new Str($this->shortString);
        $this->longStr = new Str($this->longString);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchStrLengthShort(): void
    {
        (void) $this->shortStr->length();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeLengthShort(): void
    {
        mb_strlen($this->shortString);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchStrLengthLong(): void
    {
        (void) $this->longStr->length();
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeLengthLong(): void
    {
        mb_strlen($this->longString);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchStrUpperShort(): void
    {
        (void) $this->shortStr->upper();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeUpperShort(): void
    {
        mb_strtoupper($this->shortString);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchStrUpperLong(): void
    {
        (void) $this->longStr->upper();
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeUpperLong(): void
    {
        mb_strtoupper($this->longString);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchStrLowerShort(): void
    {
        (void) $this->shortStr->lower();
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeLowerShort(): void
    {
        mb_strtolower($this->shortString);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchStrLowerLong(): void
    {
        (void) $this->longStr->lower();
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeLowerLong(): void
    {
        mb_strtolower($this->longString);
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchStrContainsShort(): void
    {
        (void) $this->shortStr->contains('World');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeContainsShort(): void
    {
        str_contains($this->shortString, 'World');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchStrContainsLong(): void
    {
        (void) $this->longStr->contains('ipsum');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeContainsLong(): void
    {
        str_contains($this->longString, 'ipsum');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchStrSplitShort(): void
    {
        (void) $this->shortStr->split(' ');
    }

    #[Revs(5000)]
    #[Iterations(10)]
    public function benchNativeSplitShort(): void
    {
        explode(' ', $this->shortString);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchStrSplitLong(): void
    {
        (void) $this->longStr->split(' ');
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeSplitLong(): void
    {
        explode(' ', $this->longString);
    }
}
