<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

readonly class DateInterval implements \Stringable
{
    private \DateInterval $value;

    /**
     * @param \DateInterval|string $value DateInterval or interval specification (e.g., 'P1D', 'PT1H')
     */
    public function __construct(\DateInterval|string $value)
    {
        $this->value = match (true) {
            $value instanceof \DateInterval => clone $value,
            default => new \DateInterval($value),
        };
    }

    /**
     * Create from DateInterval
     */
    #[\NoDiscard]
    public static function fromInterval(\DateInterval $interval): self
    {
        return new self($interval);
    }

    /**
     * Create from interval specification (P1D, PT1H, etc.)
     */
    #[\NoDiscard]
    public static function fromSpec(string $spec): self
    {
        return new self($spec);
    }

    /**
     * Create from date string (e.g., '1 day', '2 hours', '3 months')
     */
    #[\NoDiscard]
    public static function fromDateString(string $dateString): self
    {
        return new self(\DateInterval::createFromDateString($dateString));
    }

    /**
     * Create interval from date parts
     */
    #[\NoDiscard]
    public static function create(
        int $years = 0,
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0
    ): self {
        // Negative parts are ignored, as an ISO 8601 duration cannot express them
        $interval = new \DateInterval('PT0S');
        $interval->y = max(0, $years);
        $interval->m = max(0, $months);
        $interval->d = max(0, $days);
        $interval->h = max(0, $hours);
        $interval->i = max(0, $minutes);
        $interval->s = max(0, $seconds);

        return new self($interval);
    }

    /**
     * Get the underlying DateInterval
     */
    public function get(): \DateInterval
    {
        return clone $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->format('%y years, %m months, %d days, %h hours, %i minutes, %s seconds')->get();
    }

    // ==================== Formatting ====================

    /**
     * Format the interval
     *
     * Format codes:
     * %Y - Years (at least 2 digits with leading 0)
     * %y - Years
     * %M - Months (at least 2 digits with leading 0)
     * %m - Months
     * %D - Days (at least 2 digits with leading 0)
     * %d - Days
     * %a - Total number of days
     * %H - Hours (at least 2 digits with leading 0)
     * %h - Hours
     * %I - Minutes (at least 2 digits with leading 0)
     * %i - Minutes
     * %S - Seconds (at least 2 digits with leading 0)
     * %s - Seconds
     * %F - Microseconds (at least 6 digits with leading 0)
     * %f - Microseconds
     * %R - Sign "-" when negative, "+" when positive
     * %r - Sign "-" when negative, empty when positive
     * %% - Literal %
     */
    #[\NoDiscard]
    public function format(string $format): Str
    {
        return new Str($this->value->format($format));
    }

    /**
     * Format as ISO 8601 duration (P1Y2M3DT4H5M6S)
     */
    #[\NoDiscard]
    public function toIso8601(): Str
    {
        $date = $this->durationParts(['Y' => $this->value->y, 'M' => $this->value->m, 'D' => $this->value->d]);
        $time = $this->durationParts(['H' => $this->value->h, 'M' => $this->value->i, 'S' => $this->value->s]);

        if ($date === '' && $time === '') {
            return new Str('P0D');
        }

        return new Str('P' . $date . ($time === '' ? '' : 'T' . $time));
    }

    /**
     * Render non-zero duration parts, e.g. ['Y' => 1, 'M' => 0, 'D' => 3] => "1Y3D"
     *
     * @param array<string, int> $parts Unit designator => amount
     */
    private function durationParts(array $parts): string
    {
        $result = '';
        foreach ($parts as $unit => $amount) {
            if ($amount !== 0) {
                $result .= $amount . $unit;
            }
        }

        return $result;
    }

    /**
     * Format as human-readable string (1 year, 2 months, 3 days)
     */
    #[\NoDiscard]
    public function toHumanReadable(): Str
    {
        $parts = [];

        if ($this->value->y > 0) {
            $parts[] = $this->value->y . ($this->value->y === 1 ? ' year' : ' years');
        }

        if ($this->value->m > 0) {
            $parts[] = $this->value->m . ($this->value->m === 1 ? ' month' : ' months');
        }

        if ($this->value->d > 0) {
            $parts[] = $this->value->d . ($this->value->d === 1 ? ' day' : ' days');
        }

        if ($this->value->h > 0) {
            $parts[] = $this->value->h . ($this->value->h === 1 ? ' hour' : ' hours');
        }

        if ($this->value->i > 0) {
            $parts[] = $this->value->i . ($this->value->i === 1 ? ' minute' : ' minutes');
        }

        if ($this->value->s > 0) {
            $parts[] = $this->value->s . ($this->value->s === 1 ? ' second' : ' seconds');
        }

        if ($parts === []) {
            return new Str('0 seconds');
        }

        return new Str(implode(', ', $parts));
    }

    // ==================== Getters ====================

    /**
     * Get years
     */
    public function years(): int
    {
        return $this->value->y;
    }

    /**
     * Get months
     */
    public function months(): int
    {
        return $this->value->m;
    }

    /**
     * Get days
     */
    public function days(): int
    {
        return $this->value->d;
    }

    /**
     * Get hours
     */
    public function hours(): int
    {
        return $this->value->h;
    }

    /**
     * Get minutes
     */
    public function minutes(): int
    {
        return $this->value->i;
    }

    /**
     * Get seconds
     */
    public function seconds(): int
    {
        return $this->value->s;
    }

    /**
     * Get microseconds
     */
    public function microseconds(): float
    {
        return $this->value->f;
    }

    /**
     * Get total days (only available if interval was created from diff)
     */
    public function totalDays(): int|float|false
    {
        return $this->value->days;
    }

    /**
     * Check if interval is inverted (negative)
     */
    public function isInverted(): bool
    {
        return $this->value->invert === 1;
    }

    // ==================== Comparison ====================

    /**
     * Check if equals to another interval
     */
    public function equals(self|string|\DateInterval $other): bool
    {
        $otherInterval = $this->toDateInterval($other);

        return $this->value->y === $otherInterval->y
            && $this->value->m === $otherInterval->m
            && $this->value->d === $otherInterval->d
            && $this->value->h === $otherInterval->h
            && $this->value->i === $otherInterval->i
            && $this->value->s === $otherInterval->s
            && $this->value->invert === $otherInterval->invert;
    }

    // ==================== Conversion ====================

    /**
     * Convert to total seconds (approximate, assuming 30 days per month, 365 days per year)
     */
    public function toSeconds(): int
    {
        $seconds = 0;
        $seconds += $this->value->y * 365 * 24 * 3600;
        $seconds += $this->value->m * 30 * 24 * 3600;
        $seconds += $this->value->d * 24 * 3600;
        $seconds += $this->value->h * 3600;
        $seconds += $this->value->i * 60;
        $seconds += $this->value->s;

        return $this->value->invert === 1 ? -$seconds : $seconds;
    }

    /**
     * Convert to total whole minutes (approximate, truncated towards zero)
     */
    public function toMinutes(): int
    {
        return intdiv($this->toSeconds(), 60);
    }

    /**
     * Convert to total whole hours (approximate, truncated towards zero)
     */
    public function toHours(): int
    {
        return intdiv($this->toSeconds(), 3600);
    }

    /**
     * Convert to array
     *
     * @return array{
     *     years: int,
     *     months: int,
     *     days: int,
     *     hours: int,
     *     minutes: int,
     *     seconds: int,
     *     microseconds: float,
     *     total_days: int|float|false,
     *     inverted: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'years' => $this->value->y,
            'months' => $this->value->m,
            'days' => $this->value->d,
            'hours' => $this->value->h,
            'minutes' => $this->value->i,
            'seconds' => $this->value->s,
            'microseconds' => $this->value->f,
            'total_days' => $this->value->days,
            'inverted' => $this->value->invert === 1,
        ];
    }

    // ==================== Utility ====================

    /**
     * Helper to convert various interval types to DateInterval
     */
    private function toDateInterval(self|string|\DateInterval $interval): \DateInterval
    {
        return match (true) {
            $interval instanceof self => clone $interval->value,
            $interval instanceof \DateInterval => clone $interval,
            default => new \DateInterval($interval),
        };
    }
}
