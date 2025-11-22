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
    public static function fromInterval(\DateInterval $interval): self
    {
        return new self($interval);
    }

    /**
     * Create from interval specification (P1D, PT1H, etc.)
     */
    public static function fromSpec(string $spec): self
    {
        return new self($spec);
    }

    /**
     * Create from date string (e.g., '1 day', '2 hours', '3 months')
     */
    public static function fromDateString(string $dateString): self
    {
        $interval = \DateInterval::createFromDateString($dateString);
        if ($interval === false) {
            throw new \InvalidArgumentException('Failed to create interval from: ' . $dateString);
        }

        return new self($interval);
    }

    /**
     * Create interval from date parts
     */
    public static function create(
        int $years = 0,
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0
    ): self {
        $spec = 'P';
        if ($years > 0) {
            $spec .= $years . 'Y';
        }

        if ($months > 0) {
            $spec .= $months . 'M';
        }

        if ($days > 0) {
            $spec .= $days . 'D';
        }

        if ($hours > 0 || $minutes > 0 || $seconds > 0) {
            $spec .= 'T';
            if ($hours > 0) {
                $spec .= $hours . 'H';
            }

            if ($minutes > 0) {
                $spec .= $minutes . 'M';
            }

            if ($seconds > 0) {
                $spec .= $seconds . 'S';
            }
        }

        return new self($spec === 'P' ? 'P0D' : $spec);
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
    public function format(string $format): Str
    {
        return new Str($this->value->format($format));
    }

    /**
     * Format as ISO 8601 duration (P1Y2M3DT4H5M6S)
     */
    public function toIso8601(): Str
    {
        $spec = 'P';
        if ($this->value->y > 0) {
            $spec .= $this->value->y . 'Y';
        }

        if ($this->value->m > 0) {
            $spec .= $this->value->m . 'M';
        }

        if ($this->value->d > 0) {
            $spec .= $this->value->d . 'D';
        }

        $hasTime = $this->value->h > 0 || $this->value->i > 0 || $this->value->s > 0;
        if ($hasTime) {
            $spec .= 'T';
            if ($this->value->h > 0) {
                $spec .= $this->value->h . 'H';
            }

            if ($this->value->i > 0) {
                $spec .= $this->value->i . 'M';
            }

            if ($this->value->s > 0) {
                $spec .= $this->value->s . 'S';
            }
        }

        return new Str($spec === 'P' ? 'P0D' : $spec);
    }

    /**
     * Format as human-readable string (1 year, 2 months, 3 days)
     */
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
     * Convert to total minutes (approximate)
     */
    public function toMinutes(): int
    {
        return (int) floor($this->toSeconds() / 60);
    }

    /**
     * Convert to total hours (approximate)
     */
    public function toHours(): int
    {
        return (int) floor($this->toSeconds() / 3600);
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
