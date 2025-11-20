<?php

use PrettyPhp\Base\Num;
use PrettyPhp\Base\Str;

describe('Num', function (): void {
    it('can be constructed and get value', function (): void {
        $num = new Num(42);
        expect($num->get())->toBe(42);

        $numFloat = new Num(3.14);
        expect($numFloat->get())->toBe(3.14);
    });

    it('implements Stringable', function (): void {
        $num = new Num(42);
        expect((string) $num)->toBe('42');

        $numFloat = new Num(3.14);
        expect((string) $numFloat)->toBe('3.14');
    });

    // ==================== Number Formatting ====================

    describe('format', function (): void {
        it('can format with default settings', function (): void {
            $result = new Num(1234567.89)->format();
            expect($result)->toBeInstanceOf(Str::class);
            expect($result->get())->toBe('1,234,568');
        });

        it('can format with decimals', function (): void {
            expect(new Num(1234.5678)->format(2)->get())->toBe('1,234.57');
            expect(new Num(1234.5678)->format(3)->get())->toBe('1,234.568');
        });

        it('can format with custom separators', function (): void {
            expect(new Num(1234.56)->format(2, ',', ' ')->get())->toBe('1 234,56');
            expect(new Num(1234.56)->format(2, ',', '.')->get())->toBe('1.234,56');
        });
    });

    describe('currency', function (): void {
        it('can format as currency with default settings', function (): void {
            $result = new Num(1234.56)->currency();
            expect($result)->toBeInstanceOf(Str::class);
            expect($result->get())->toBe('$1,234.56');
        });

        it('can format with custom currency symbol', function (): void {
            expect(new Num(1234.56)->currency('€')->get())->toBe('€1,234.56');
            expect(new Num(1234.56)->currency('£')->get())->toBe('£1,234.56');
        });

        it('can format with symbol after number', function (): void {
            expect(new Num(1234.56)->currency('€', 2, '.', ',', true)->get())->toBe('1,234.56€');
        });

        it('can format with custom decimals', function (): void {
            expect(new Num(1234.567)->currency('$', 3)->get())->toBe('$1,234.567');
            expect(new Num(1234)->currency('$', 0)->get())->toBe('$1,234');
        });

        it('can format with custom separators', function (): void {
            expect(new Num(1234.56)->currency('€', 2, ',', ' ')->get())->toBe('€1 234,56');
        });
    });

    // ==================== Math Operations ====================

    describe('round', function (): void {
        it('can round to nearest integer', function (): void {
            expect(new Num(3.14159)->round()->get())->toBe(3.0);
            expect(new Num(3.6)->round()->get())->toBe(4.0);
        });

        it('can round with precision', function (): void {
            expect(new Num(3.14159)->round(2)->get())->toBe(3.14);
            expect(new Num(3.14159)->round(3)->get())->toBe(3.142);
        });

        it('works with negative numbers', function (): void {
            expect(new Num(-3.14159)->round(2)->get())->toBe(-3.14);
        });
    });

    describe('floor', function (): void {
        it('can round down', function (): void {
            expect(new Num(3.9)->floor()->get())->toBe(3);
            expect(new Num(3.1)->floor()->get())->toBe(3);
        });

        it('works with negative numbers', function (): void {
            expect(new Num(-3.1)->floor()->get())->toBe(-4);
        });
    });

    describe('ceil', function (): void {
        it('can round up', function (): void {
            expect(new Num(3.1)->ceil()->get())->toBe(4);
            expect(new Num(3.9)->ceil()->get())->toBe(4);
        });

        it('works with negative numbers', function (): void {
            expect(new Num(-3.9)->ceil()->get())->toBe(-3);
        });
    });

    describe('abs', function (): void {
        it('can get absolute value', function (): void {
            expect(new Num(-42)->abs()->get())->toBe(42);
            expect(new Num(42)->abs()->get())->toBe(42);
            expect(new Num(-3.14)->abs()->get())->toBe(3.14);
        });
    });

    describe('clamp', function (): void {
        it('can clamp value within range', function (): void {
            expect(new Num(5)->clamp(0, 10)->get())->toBe(5);
            expect(new Num(-5)->clamp(0, 10)->get())->toBe(0);
            expect(new Num(15)->clamp(0, 10)->get())->toBe(10);
        });

        it('works with floats', function (): void {
            expect(new Num(5.5)->clamp(0.0, 10.0)->get())->toBe(5.5);
            expect(new Num(-1.5)->clamp(0.0, 10.0)->get())->toBe(0.0);
        });

        it('throws exception when min > max', function (): void {
            expect(fn() => new Num(5)->clamp(10, 0))
                ->toThrow(\InvalidArgumentException::class, 'Min value cannot be greater than max value');
        });
    });

    describe('arithmetic operations', function (): void {
        it('can add', function (): void {
            expect(new Num(5)->add(3)->get())->toBe(8);
            expect(new Num(5.5)->add(2.5)->get())->toBe(8.0);
        });

        it('can subtract', function (): void {
            expect(new Num(5)->subtract(3)->get())->toBe(2);
            expect(new Num(5.5)->subtract(2.5)->get())->toBe(3.0);
        });

        it('can multiply', function (): void {
            expect(new Num(5)->multiply(3)->get())->toBe(15);
            expect(new Num(2.5)->multiply(2)->get())->toBe(5.0);
        });

        it('can divide', function (): void {
            expect(new Num(10)->divide(2)->get())->toBe(5);
            expect(new Num(5)->divide(2)->get())->toBe(2.5);
        });

        it('throws exception on division by zero', function (): void {
            expect(fn() => new Num(10)->divide(0))
                ->toThrow(\DivisionByZeroError::class, 'Division by zero');
        });

        it('can calculate modulo', function (): void {
            expect(new Num(10)->mod(3)->get())->toBe(1.0);
            expect(new Num(7.5)->mod(2.5)->get())->toBe(0.0);
        });

        it('throws exception on modulo by zero', function (): void {
            expect(fn() => new Num(10)->mod(0))
                ->toThrow(\DivisionByZeroError::class, 'Modulo by zero');
        });

        it('can calculate power', function (): void {
            expect(new Num(2)->pow(3)->get())->toBe(8);
            expect(new Num(5)->pow(2)->get())->toBe(25);
        });

        it('can calculate square root', function (): void {
            expect(new Num(16)->sqrt()->get())->toBe(4.0);
            expect(new Num(25)->sqrt()->get())->toBe(5.0);
        });

        it('throws exception on square root of negative', function (): void {
            expect(fn() => new Num(-16)->sqrt())
                ->toThrow(\InvalidArgumentException::class, 'Cannot calculate square root of negative number');
        });
    });

    // ==================== Validation ====================

    describe('isEven', function (): void {
        it('can check if number is even', function (): void {
            expect(new Num(2)->isEven())->toBeTrue();
            expect(new Num(4)->isEven())->toBeTrue();
            expect(new Num(0)->isEven())->toBeTrue();
            expect(new Num(3)->isEven())->toBeFalse();
            expect(new Num(-2)->isEven())->toBeTrue();
        });

        it('returns false for floats', function (): void {
            expect(new Num(2.0)->isEven())->toBeFalse();
        });
    });

    describe('isOdd', function (): void {
        it('can check if number is odd', function (): void {
            expect(new Num(3)->isOdd())->toBeTrue();
            expect(new Num(5)->isOdd())->toBeTrue();
            expect(new Num(-3)->isOdd())->toBeTrue();
            expect(new Num(2)->isOdd())->toBeFalse();
            expect(new Num(0)->isOdd())->toBeFalse();
        });

        it('returns false for floats', function (): void {
            expect(new Num(3.0)->isOdd())->toBeFalse();
        });
    });

    describe('isPrime', function (): void {
        it('can check if number is prime', function (): void {
            expect(new Num(2)->isPrime())->toBeTrue();
            expect(new Num(3)->isPrime())->toBeTrue();
            expect(new Num(5)->isPrime())->toBeTrue();
            expect(new Num(7)->isPrime())->toBeTrue();
            expect(new Num(11)->isPrime())->toBeTrue();
            expect(new Num(13)->isPrime())->toBeTrue();
        });

        it('returns false for non-prime numbers', function (): void {
            expect(new Num(0)->isPrime())->toBeFalse();
            expect(new Num(1)->isPrime())->toBeFalse();
            expect(new Num(4)->isPrime())->toBeFalse();
            expect(new Num(6)->isPrime())->toBeFalse();
            expect(new Num(8)->isPrime())->toBeFalse();
            expect(new Num(9)->isPrime())->toBeFalse();
            expect(new Num(10)->isPrime())->toBeFalse();
        });

        it('returns false for negative numbers', function (): void {
            expect(new Num(-2)->isPrime())->toBeFalse();
            expect(new Num(-5)->isPrime())->toBeFalse();
        });

        it('returns false for floats', function (): void {
            expect(new Num(2.5)->isPrime())->toBeFalse();
        });

        it('works with larger primes', function (): void {
            expect(new Num(97)->isPrime())->toBeTrue();
            expect(new Num(100)->isPrime())->toBeFalse();
        });
    });

    describe('inRange', function (): void {
        it('can check if number is in range', function (): void {
            expect(new Num(5)->inRange(0, 10))->toBeTrue();
            expect(new Num(0)->inRange(0, 10))->toBeTrue();
            expect(new Num(10)->inRange(0, 10))->toBeTrue();
            expect(new Num(-1)->inRange(0, 10))->toBeFalse();
            expect(new Num(11)->inRange(0, 10))->toBeFalse();
        });

        it('works with floats', function (): void {
            expect(new Num(5.5)->inRange(0.0, 10.0))->toBeTrue();
            expect(new Num(-0.1)->inRange(0.0, 10.0))->toBeFalse();
        });
    });

    describe('sign checks', function (): void {
        it('can check if positive', function (): void {
            expect(new Num(5)->isPositive())->toBeTrue();
            expect(new Num(0)->isPositive())->toBeFalse();
            expect(new Num(-5)->isPositive())->toBeFalse();
        });

        it('can check if negative', function (): void {
            expect(new Num(-5)->isNegative())->toBeTrue();
            expect(new Num(0)->isNegative())->toBeFalse();
            expect(new Num(5)->isNegative())->toBeFalse();
        });

        it('can check if zero', function (): void {
            expect(new Num(0)->isZero())->toBeTrue();
            expect(new Num(0.0)->isZero())->toBeTrue();
            expect(new Num(5)->isZero())->toBeFalse();
            expect(new Num(-5)->isZero())->toBeFalse();
        });
    });

    describe('type checks', function (): void {
        it('can check if int', function (): void {
            expect(new Num(5)->isInt())->toBeTrue();
            expect(new Num(5.0)->isInt())->toBeFalse();
            expect(new Num(5.5)->isInt())->toBeFalse();
        });

        it('can check if float', function (): void {
            expect(new Num(5.5)->isFloat())->toBeTrue();
            expect(new Num(5.0)->isFloat())->toBeTrue();
            expect(new Num(5)->isFloat())->toBeFalse();
        });
    });

    // ==================== Base Conversion ====================

    describe('toHex', function (): void {
        it('can convert to hex', function (): void {
            expect(new Num(255)->toHex()->get())->toBe('ff');
            expect(new Num(16)->toHex()->get())->toBe('10');
            expect(new Num(0)->toHex()->get())->toBe('0');
        });

        it('can convert with prefix', function (): void {
            expect(new Num(255)->toHex(true)->get())->toBe('0xff');
        });

        it('throws exception for floats', function (): void {
            expect(fn() => new Num(3.14)->toHex())
                ->toThrow(\InvalidArgumentException::class, 'Hex conversion only works with integers');
        });
    });

    describe('toBinary', function (): void {
        it('can convert to binary', function (): void {
            expect(new Num(5)->toBinary()->get())->toBe('101');
            expect(new Num(8)->toBinary()->get())->toBe('1000');
            expect(new Num(0)->toBinary()->get())->toBe('0');
        });

        it('can convert with prefix', function (): void {
            expect(new Num(5)->toBinary(true)->get())->toBe('0b101');
        });

        it('throws exception for floats', function (): void {
            expect(fn() => new Num(3.14)->toBinary())
                ->toThrow(\InvalidArgumentException::class, 'Binary conversion only works with integers');
        });
    });

    describe('toOctal', function (): void {
        it('can convert to octal', function (): void {
            expect(new Num(8)->toOctal()->get())->toBe('10');
            expect(new Num(64)->toOctal()->get())->toBe('100');
            expect(new Num(0)->toOctal()->get())->toBe('0');
        });

        it('can convert with prefix', function (): void {
            expect(new Num(8)->toOctal(true)->get())->toBe('0o10');
        });

        it('throws exception for floats', function (): void {
            expect(fn() => new Num(3.14)->toOctal())
                ->toThrow(\InvalidArgumentException::class, 'Octal conversion only works with integers');
        });
    });

    describe('fromHex', function (): void {
        it('can convert from hex', function (): void {
            expect(Num::fromHex('ff')->get())->toBe(255);
            expect(Num::fromHex('10')->get())->toBe(16);
            expect(Num::fromHex('0')->get())->toBe(0);
        });

        it('can convert with prefix', function (): void {
            expect(Num::fromHex('0xff')->get())->toBe(255);
            expect(Num::fromHex('0X10')->get())->toBe(16);
        });
    });

    describe('fromBinary', function (): void {
        it('can convert from binary', function (): void {
            expect(Num::fromBinary('101')->get())->toBe(5);
            expect(Num::fromBinary('1000')->get())->toBe(8);
            expect(Num::fromBinary('0')->get())->toBe(0);
        });

        it('can convert with prefix', function (): void {
            expect(Num::fromBinary('0b101')->get())->toBe(5);
            expect(Num::fromBinary('0B1000')->get())->toBe(8);
        });
    });

    describe('fromOctal', function (): void {
        it('can convert from octal', function (): void {
            expect(Num::fromOctal('10')->get())->toBe(8);
            expect(Num::fromOctal('100')->get())->toBe(64);
            expect(Num::fromOctal('0')->get())->toBe(0);
        });

        it('can convert with prefix', function (): void {
            expect(Num::fromOctal('0o10')->get())->toBe(8);
            expect(Num::fromOctal('0O100')->get())->toBe(64);
        });
    });

    // ==================== Comparison ====================

    describe('comparison', function (): void {
        it('can check equality', function (): void {
            expect(new Num(5)->equals(5))->toBeTrue();
            expect(new Num(5)->equals(6))->toBeFalse();
        });

        it('can check greater than', function (): void {
            expect(new Num(5)->greaterThan(3))->toBeTrue();
            expect(new Num(5)->greaterThan(5))->toBeFalse();
            expect(new Num(5)->greaterThan(7))->toBeFalse();
        });

        it('can check greater than or equal', function (): void {
            expect(new Num(5)->greaterThanOrEqual(3))->toBeTrue();
            expect(new Num(5)->greaterThanOrEqual(5))->toBeTrue();
            expect(new Num(5)->greaterThanOrEqual(7))->toBeFalse();
        });

        it('can check less than', function (): void {
            expect(new Num(5)->lessThan(7))->toBeTrue();
            expect(new Num(5)->lessThan(5))->toBeFalse();
            expect(new Num(5)->lessThan(3))->toBeFalse();
        });

        it('can check less than or equal', function (): void {
            expect(new Num(5)->lessThanOrEqual(7))->toBeTrue();
            expect(new Num(5)->lessThanOrEqual(5))->toBeTrue();
            expect(new Num(5)->lessThanOrEqual(3))->toBeFalse();
        });

        it('can get minimum', function (): void {
            expect(new Num(5)->min(3)->get())->toBe(3);
            expect(new Num(5)->min(7)->get())->toBe(5);
        });

        it('can get maximum', function (): void {
            expect(new Num(5)->max(3)->get())->toBe(5);
            expect(new Num(5)->max(7)->get())->toBe(7);
        });
    });

    // ==================== Utility ====================

    describe('conversion', function (): void {
        it('can convert to int', function (): void {
            expect(new Num(5)->toInt())->toBe(5);
            expect(new Num(5.9)->toInt())->toBe(5);
        });

        it('can convert to float', function (): void {
            expect(new Num(5)->toFloat())->toBe(5.0);
            expect(new Num(5.9)->toFloat())->toBe(5.9);
        });
    });
});
