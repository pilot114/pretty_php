<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

readonly class Num implements \Stringable
{
    public function __construct(
        private int|float $value
    ) {
    }

    public function get(): int|float
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->value;
    }

    // ==================== Number Formatting ====================

    /**
     * Format number with thousands separator and decimal places
     */
    public function format(
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): Str {
        return new Str(number_format($this->value, $decimals, $decimalSeparator, $thousandsSeparator));
    }

    /**
     * Format as currency
     */
    public function currency(
        string $currencySymbol = '$',
        int $decimals = 2,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
        bool $symbolAfter = false
    ): Str {
        $formatted = number_format($this->value, $decimals, $decimalSeparator, $thousandsSeparator);
        $result = $symbolAfter
            ? $formatted . $currencySymbol
            : $currencySymbol . $formatted;

        return new Str($result);
    }

    // ==================== Math Operations ====================

    /**
     * Round to specified precision
     */
    public function round(int $precision = 0): self
    {
        return new self(round($this->value, $precision));
    }

    /**
     * Round down to nearest integer
     */
    public function floor(): self
    {
        return new self((int) floor($this->value));
    }

    /**
     * Round up to nearest integer
     */
    public function ceil(): self
    {
        return new self((int) ceil($this->value));
    }

    /**
     * Get absolute value
     */
    public function abs(): self
    {
        return new self(abs($this->value));
    }

    /**
     * Clamp value between min and max
     */
    public function clamp(int|float $min, int|float $max): self
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min value cannot be greater than max value');
        }

        $clamped = max($min, min($max, $this->value));
        return new self($clamped);
    }

    /**
     * Add a number
     */
    public function add(int|float $number): self
    {
        return new self($this->value + $number);
    }

    /**
     * Subtract a number
     */
    public function subtract(int|float $number): self
    {
        return new self($this->value - $number);
    }

    /**
     * Multiply by a number
     */
    public function multiply(int|float $number): self
    {
        return new self($this->value * $number);
    }

    /**
     * Divide by a number
     */
    public function divide(int|float $number): self
    {
        if ($number === 0 || $number === 0.0) {
            throw new \DivisionByZeroError('Division by zero');
        }

        return new self($this->value / $number);
    }

    /**
     * Modulo operation
     */
    public function mod(int|float $number): self
    {
        if ($number === 0 || $number === 0.0) {
            throw new \DivisionByZeroError('Modulo by zero');
        }

        return new self(fmod($this->value, $number));
    }

    /**
     * Power operation
     */
    public function pow(int|float $exponent): self
    {
        return new self($this->value ** $exponent);
    }

    /**
     * Square root
     */
    public function sqrt(): self
    {
        if ($this->value < 0) {
            throw new \InvalidArgumentException('Cannot calculate square root of negative number');
        }

        return new self(sqrt($this->value));
    }

    // ==================== Validation ====================

    /**
     * Check if number is even
     */
    public function isEven(): bool
    {
        return is_int($this->value) && $this->value % 2 === 0;
    }

    /**
     * Check if number is odd
     */
    public function isOdd(): bool
    {
        return is_int($this->value) && $this->value % 2 !== 0;
    }

    /**
     * Check if number is prime
     */
    public function isPrime(): bool
    {
        if (!is_int($this->value) || $this->value < 2) {
            return false;
        }

        if ($this->value === 2) {
            return true;
        }

        if ($this->value % 2 === 0) {
            return false;
        }

        $sqrt = (int) sqrt($this->value);
        for ($i = 3; $i <= $sqrt; $i += 2) {
            if ($this->value % $i === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if number is in range (inclusive)
     */
    public function inRange(int|float $min, int|float $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    /**
     * Check if number is positive
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Check if number is negative
     */
    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    /**
     * Check if number is zero
     */
    public function isZero(): bool
    {
        return $this->value === 0 || $this->value === 0.0;
    }

    /**
     * Check if number is integer
     */
    public function isInt(): bool
    {
        return is_int($this->value);
    }

    /**
     * Check if number is float
     */
    public function isFloat(): bool
    {
        return is_float($this->value);
    }

    // ==================== Base Conversion ====================

    /**
     * Convert to hexadecimal string
     */
    public function toHex(bool $prefix = false): Str
    {
        if (!is_int($this->value)) {
            throw new \InvalidArgumentException('Hex conversion only works with integers');
        }

        $hex = dechex($this->value);
        return new Str($prefix ? '0x' . $hex : $hex);
    }

    /**
     * Convert to binary string
     */
    public function toBinary(bool $prefix = false): Str
    {
        if (!is_int($this->value)) {
            throw new \InvalidArgumentException('Binary conversion only works with integers');
        }

        $binary = decbin($this->value);
        return new Str($prefix ? '0b' . $binary : $binary);
    }

    /**
     * Convert to octal string
     */
    public function toOctal(bool $prefix = false): Str
    {
        if (!is_int($this->value)) {
            throw new \InvalidArgumentException('Octal conversion only works with integers');
        }

        $octal = decoct($this->value);
        return new Str($prefix ? '0o' . $octal : $octal);
    }

    /**
     * Convert from hexadecimal string
     */
    public static function fromHex(string $hex): self
    {
        $hex = str_replace(['0x', '0X'], '', $hex);
        $value = hexdec($hex);
        return new self((int) $value);
    }

    /**
     * Convert from binary string
     */
    public static function fromBinary(string $binary): self
    {
        $binary = str_replace(['0b', '0B'], '', $binary);
        $value = bindec($binary);
        return new self((int) $value);
    }

    /**
     * Convert from octal string
     */
    public static function fromOctal(string $octal): self
    {
        $octal = str_replace(['0o', '0O'], '', $octal);
        $value = octdec($octal);
        return new self((int) $value);
    }

    // ==================== Comparison ====================

    /**
     * Check if equals to another number
     */
    public function equals(int|float $other): bool
    {
        return $this->value === $other;
    }

    /**
     * Check if greater than another number
     */
    public function greaterThan(int|float $other): bool
    {
        return $this->value > $other;
    }

    /**
     * Check if greater than or equal to another number
     */
    public function greaterThanOrEqual(int|float $other): bool
    {
        return $this->value >= $other;
    }

    /**
     * Check if less than another number
     */
    public function lessThan(int|float $other): bool
    {
        return $this->value < $other;
    }

    /**
     * Check if less than or equal to another number
     */
    public function lessThanOrEqual(int|float $other): bool
    {
        return $this->value <= $other;
    }

    /**
     * Get the minimum of this and another number
     */
    public function min(int|float $other): self
    {
        return new self(min($this->value, $other));
    }

    /**
     * Get the maximum of this and another number
     */
    public function max(int|float $other): self
    {
        return new self(max($this->value, $other));
    }

    // ==================== Utility ====================

    /**
     * Convert to integer
     */
    public function toInt(): int
    {
        return (int) $this->value;
    }

    /**
     * Convert to float
     */
    public function toFloat(): float
    {
        return (float) $this->value;
    }
}
