<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

readonly class DateTime implements \Stringable
{
    private \DateTimeImmutable $value;

    /**
     * @param \DateTimeImmutable|string|int|null $value DateTimeImmutable, string, timestamp, or null for now
     * @param \DateTimeZone|string|null $timezone Timezone
     */
    public function __construct(
        \DateTimeImmutable|string|int|null $value = null,
        \DateTimeZone|string|null $timezone = null
    ) {
        $tz = match (true) {
            $timezone === null => null,
            is_string($timezone) => new \DateTimeZone($timezone),
            default => $timezone,
        };

        $this->value = match (true) {
            $value instanceof \DateTimeImmutable => $tz instanceof \DateTimeZone ? $value->setTimezone($tz) : $value,
            is_int($value) => new \DateTimeImmutable('@' . $value)->setTimezone($tz ?? new \DateTimeZone('UTC')),
            is_string($value) => new \DateTimeImmutable($value, $tz),
            default => new \DateTimeImmutable('now', $tz),
        };
    }

    /**
     * Create from DateTimeImmutable
     */
    public static function fromImmutable(\DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    /**
     * Create from DateTime
     */
    public static function fromMutable(\DateTime $dateTime): self
    {
        return new self(\DateTimeImmutable::createFromMutable($dateTime));
    }

    /**
     * Create from timestamp
     */
    public static function fromTimestamp(int $timestamp, \DateTimeZone|string|null $timezone = null): self
    {
        return new self($timestamp, $timezone);
    }

    /**
     * Create from format
     */
    public static function fromFormat(
        string $format,
        string $datetime,
        \DateTimeZone|string|null $timezone = null
    ): self {
        $tz = match (true) {
            $timezone === null => null,
            is_string($timezone) => new \DateTimeZone($timezone),
            default => $timezone,
        };

        $dt = \DateTimeImmutable::createFromFormat($format, $datetime, $tz);
        if ($dt === false) {
            throw new \InvalidArgumentException('Failed to parse datetime: ' . $datetime);
        }

        return new self($dt);
    }

    /**
     * Parse a datetime string
     */
    public static function parse(string $datetime, \DateTimeZone|string|null $timezone = null): self
    {
        return new self($datetime, $timezone);
    }

    /**
     * Create current datetime
     */
    public static function now(\DateTimeZone|string|null $timezone = null): self
    {
        return new self(null, $timezone);
    }

    /**
     * Create today at midnight
     */
    public static function today(\DateTimeZone|string|null $timezone = null): self
    {
        return new self(null, $timezone)->startOfDay();
    }

    /**
     * Create tomorrow at midnight
     */
    public static function tomorrow(\DateTimeZone|string|null $timezone = null): self
    {
        return new self(null, $timezone)->addDays(1)->startOfDay();
    }

    /**
     * Create yesterday at midnight
     */
    public static function yesterday(\DateTimeZone|string|null $timezone = null): self
    {
        return new self(null, $timezone)->subDays(1)->startOfDay();
    }

    /**
     * Get the underlying DateTimeImmutable
     */
    public function get(): \DateTimeImmutable
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value->format('Y-m-d H:i:s');
    }

    // ==================== Formatting ====================

    /**
     * Format the datetime
     */
    public function format(string $format): Str
    {
        return new Str($this->value->format($format));
    }

    /**
     * Format as ISO 8601 (2024-01-15T14:30:00+00:00)
     */
    public function toIso8601(): Str
    {
        return new Str($this->value->format(\DateTimeInterface::ATOM));
    }

    /**
     * Format as RFC 2822 (Mon, 15 Jan 2024 14:30:00 +0000)
     */
    public function toRfc2822(): Str
    {
        return new Str($this->value->format(\DateTimeInterface::RFC2822));
    }

    /**
     * Format as RFC 3339 (2024-01-15T14:30:00+00:00)
     */
    public function toRfc3339(): Str
    {
        return new Str($this->value->format(\DateTimeInterface::RFC3339));
    }

    /**
     * Format as date only (Y-m-d)
     */
    public function toDateString(): Str
    {
        return new Str($this->value->format('Y-m-d'));
    }

    /**
     * Format as time only (H:i:s)
     */
    public function toTimeString(): Str
    {
        return new Str($this->value->format('H:i:s'));
    }

    /**
     * Format as datetime string (Y-m-d H:i:s)
     */
    public function toDateTimeString(): Str
    {
        return new Str($this->value->format('Y-m-d H:i:s'));
    }

    /**
     * Get timestamp
     */
    public function timestamp(): int
    {
        return $this->value->getTimestamp();
    }

    // ==================== Comparison ====================

    /**
     * Check if equals to another datetime
     */
    public function equals(self|string|\DateTimeInterface $other): bool
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return $this->value->getTimestamp() === $otherDt->getTimestamp();
    }

    /**
     * Check if before another datetime
     */
    public function isBefore(self|string|\DateTimeInterface $other): bool
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return $this->value < $otherDt;
    }

    /**
     * Check if after another datetime
     */
    public function isAfter(self|string|\DateTimeInterface $other): bool
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return $this->value > $otherDt;
    }

    /**
     * Check if before or equal to another datetime
     */
    public function isBeforeOrEqual(self|string|\DateTimeInterface $other): bool
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return $this->value <= $otherDt;
    }

    /**
     * Check if after or equal to another datetime
     */
    public function isAfterOrEqual(self|string|\DateTimeInterface $other): bool
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return $this->value >= $otherDt;
    }

    /**
     * Check if between two datetimes
     */
    public function isBetween(
        self|string|\DateTimeInterface $start,
        self|string|\DateTimeInterface $end,
        bool $inclusive = true
    ): bool {
        $startDt = $this->toDateTimeImmutable($start);
        $endDt = $this->toDateTimeImmutable($end);

        if ($inclusive) {
            return $this->value >= $startDt && $this->value <= $endDt;
        }

        return $this->value > $startDt && $this->value < $endDt;
    }

    /**
     * Check if in the past
     */
    public function isPast(): bool
    {
        return $this->value < new \DateTimeImmutable();
    }

    /**
     * Check if in the future
     */
    public function isFuture(): bool
    {
        return $this->value > new \DateTimeImmutable();
    }

    /**
     * Check if is today
     */
    public function isToday(): bool
    {
        $today = new \DateTimeImmutable();
        return $this->value->format('Y-m-d') === $today->format('Y-m-d');
    }

    /**
     * Check if is yesterday
     */
    public function isYesterday(): bool
    {
        $yesterday = new \DateTimeImmutable()->modify('-1 day');
        return $this->value->format('Y-m-d') === $yesterday->format('Y-m-d');
    }

    /**
     * Check if is tomorrow
     */
    public function isTomorrow(): bool
    {
        $tomorrow = new \DateTimeImmutable()->modify('+1 day');
        return $this->value->format('Y-m-d') === $tomorrow->format('Y-m-d');
    }

    /**
     * Check if is a weekend
     */
    public function isWeekend(): bool
    {
        return in_array((int) $this->value->format('N'), [6, 7], true);
    }

    /**
     * Check if is a weekday
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    // ==================== Modification ====================

    /**
     * Add years
     */
    public function addYears(int $years): self
    {
        return new self($this->value->modify(sprintf('+%d years', $years)));
    }

    /**
     * Add months
     */
    public function addMonths(int $months): self
    {
        return new self($this->value->modify(sprintf('+%d months', $months)));
    }

    /**
     * Add days
     */
    public function addDays(int $days): self
    {
        return new self($this->value->modify(sprintf('+%d days', $days)));
    }

    /**
     * Add hours
     */
    public function addHours(int $hours): self
    {
        return new self($this->value->modify(sprintf('+%d hours', $hours)));
    }

    /**
     * Add minutes
     */
    public function addMinutes(int $minutes): self
    {
        return new self($this->value->modify(sprintf('+%d minutes', $minutes)));
    }

    /**
     * Add seconds
     */
    public function addSeconds(int $seconds): self
    {
        return new self($this->value->modify(sprintf('+%d seconds', $seconds)));
    }

    /**
     * Subtract years
     */
    public function subYears(int $years): self
    {
        return new self($this->value->modify(sprintf('-%d years', $years)));
    }

    /**
     * Subtract months
     */
    public function subMonths(int $months): self
    {
        return new self($this->value->modify(sprintf('-%d months', $months)));
    }

    /**
     * Subtract days
     */
    public function subDays(int $days): self
    {
        return new self($this->value->modify(sprintf('-%d days', $days)));
    }

    /**
     * Subtract hours
     */
    public function subHours(int $hours): self
    {
        return new self($this->value->modify(sprintf('-%d hours', $hours)));
    }

    /**
     * Subtract minutes
     */
    public function subMinutes(int $minutes): self
    {
        return new self($this->value->modify(sprintf('-%d minutes', $minutes)));
    }

    /**
     * Subtract seconds
     */
    public function subSeconds(int $seconds): self
    {
        return new self($this->value->modify(sprintf('-%d seconds', $seconds)));
    }

    /**
     * Add interval
     */
    public function add(\DateInterval|DateInterval $interval): self
    {
        $int = $interval instanceof DateInterval ? $interval->get() : $interval;
        return new self($this->value->add($int));
    }

    /**
     * Subtract interval
     */
    public function sub(\DateInterval|DateInterval $interval): self
    {
        $int = $interval instanceof DateInterval ? $interval->get() : $interval;
        return new self($this->value->sub($int));
    }

    /**
     * Modify using relative formats
     */
    public function modify(string $modifier): self
    {
        try {
            $result = $this->value->modify($modifier);
            if ($result === false) {
                throw new \InvalidArgumentException('Invalid modifier: ' . $modifier);
            }

            return new self($result);
        } catch (\DateMalformedStringException $dateMalformedStringException) {
            throw new \InvalidArgumentException('Invalid modifier: ' . $modifier, 0, $dateMalformedStringException);
        }
    }

    // ==================== Set Components ====================

    /**
     * Set the year
     */
    public function setYear(int $year): self
    {
        return new self($this->value->setDate($year, (int) $this->value->format('n'), (int) $this->value->format('j')));
    }

    /**
     * Set the month
     */
    public function setMonth(int $month): self
    {
        $year = (int) $this->value->format('Y');
        $day = (int) $this->value->format('j');
        return new self($this->value->setDate($year, $month, $day));
    }

    /**
     * Set the day
     */
    public function setDay(int $day): self
    {
        return new self($this->value->setDate((int) $this->value->format('Y'), (int) $this->value->format('n'), $day));
    }

    /**
     * Set the time
     */
    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): self
    {
        return new self($this->value->setTime($hour, $minute, $second, $microsecond));
    }

    /**
     * Set the date
     */
    public function setDate(int $year, int $month, int $day): self
    {
        return new self($this->value->setDate($year, $month, $day));
    }

    /**
     * Set to start of day (00:00:00)
     */
    public function startOfDay(): self
    {
        return new self($this->value->setTime(0, 0, 0, 0));
    }

    /**
     * Set to end of day (23:59:59)
     */
    public function endOfDay(): self
    {
        return new self($this->value->setTime(23, 59, 59, 999999));
    }

    /**
     * Set to start of month
     */
    public function startOfMonth(): self
    {
        return new self($this->value->modify('first day of this month')->setTime(0, 0, 0, 0));
    }

    /**
     * Set to end of month
     */
    public function endOfMonth(): self
    {
        return new self($this->value->modify('last day of this month')->setTime(23, 59, 59, 999999));
    }

    /**
     * Set to start of year
     */
    public function startOfYear(): self
    {
        return new self($this->value->setDate((int) $this->value->format('Y'), 1, 1)->setTime(0, 0, 0, 0));
    }

    /**
     * Set to end of year
     */
    public function endOfYear(): self
    {
        return new self($this->value->setDate((int) $this->value->format('Y'), 12, 31)->setTime(23, 59, 59, 999999));
    }

    /**
     * Set to start of week (Monday)
     */
    public function startOfWeek(): self
    {
        return new self($this->value->modify('monday this week')->setTime(0, 0, 0, 0));
    }

    /**
     * Set to end of week (Sunday)
     */
    public function endOfWeek(): self
    {
        return new self($this->value->modify('sunday this week')->setTime(23, 59, 59, 999999));
    }

    // ==================== Timezone ====================

    /**
     * Get the timezone
     */
    public function timezone(): \DateTimeZone
    {
        return $this->value->getTimezone();
    }

    /**
     * Get the timezone as Timezone object
     */
    public function tz(): Timezone
    {
        return new Timezone($this->value->getTimezone());
    }

    /**
     * Get the timezone name
     */
    public function timezoneName(): Str
    {
        return new Str($this->value->getTimezone()->getName());
    }

    /**
     * Convert to timezone
     */
    public function toTimezone(\DateTimeZone|Timezone|string $timezone): self
    {
        $tz = match (true) {
            $timezone instanceof Timezone => $timezone->get(),
            is_string($timezone) => new \DateTimeZone($timezone),
            default => $timezone,
        };
        return new self($this->value->setTimezone($tz));
    }

    /**
     * Convert to UTC
     */
    public function toUtc(): self
    {
        return $this->toTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Get timezone offset in seconds
     */
    public function timezoneOffset(): int
    {
        return $this->value->getOffset();
    }

    // ==================== Difference ====================

    /**
     * Get difference from another datetime
     */
    public function diff(self|string|\DateTimeInterface $other, bool $absolute = false): DateInterval
    {
        $otherDt = $this->toDateTimeImmutable($other);
        return new DateInterval($this->value->diff($otherDt, $absolute));
    }

    /**
     * Get difference in years
     */
    public function diffInYears(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        return $this->diff($other, $absolute)->years();
    }

    /**
     * Get difference in months
     */
    public function diffInMonths(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        $diff = $this->diff($other, $absolute);
        return ($diff->years() * 12) + $diff->months();
    }

    /**
     * Get difference in days
     */
    public function diffInDays(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        $otherDt = $this->toDateTimeImmutable($other);
        $diff = $this->value->getTimestamp() - $otherDt->getTimestamp();
        $days = (int) floor(abs($diff) / 86400);
        return $absolute ? $days : ($diff >= 0 ? $days : -$days);
    }

    /**
     * Get difference in hours
     */
    public function diffInHours(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        $otherDt = $this->toDateTimeImmutable($other);
        $diff = $this->value->getTimestamp() - $otherDt->getTimestamp();
        $diff = $absolute ? abs($diff) : $diff;
        return (int) floor($diff / 3600);
    }

    /**
     * Get difference in minutes
     */
    public function diffInMinutes(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        $otherDt = $this->toDateTimeImmutable($other);
        $diff = $this->value->getTimestamp() - $otherDt->getTimestamp();
        $diff = $absolute ? abs($diff) : $diff;
        return (int) floor($diff / 60);
    }

    /**
     * Get difference in seconds
     */
    public function diffInSeconds(self|string|\DateTimeInterface $other, bool $absolute = true): int
    {
        $otherDt = $this->toDateTimeImmutable($other);
        $diff = $this->value->getTimestamp() - $otherDt->getTimestamp();
        return $absolute ? abs($diff) : $diff;
    }

    // ==================== Relative Time ====================

    /**
     * Get human-readable time ago (e.g., "2 hours ago", "3 days ago")
     */
    public function ago(): Str
    {
        $now = new \DateTimeImmutable();
        $diff = $this->value->diff($now);

        if ($diff->y > 0) {
            return new Str($diff->y === 1 ? '1 year ago' : $diff->y . ' years ago');
        }

        if ($diff->m > 0) {
            return new Str($diff->m === 1 ? '1 month ago' : $diff->m . ' months ago');
        }

        if ($diff->d > 0) {
            if ($diff->d >= 7) {
                $weeks = (int) floor($diff->d / 7);
                return new Str($weeks === 1 ? '1 week ago' : $weeks . ' weeks ago');
            }

            return new Str($diff->d === 1 ? '1 day ago' : $diff->d . ' days ago');
        }

        if ($diff->h > 0) {
            return new Str($diff->h === 1 ? '1 hour ago' : $diff->h . ' hours ago');
        }

        if ($diff->i > 0) {
            return new Str($diff->i === 1 ? '1 minute ago' : $diff->i . ' minutes ago');
        }

        return new Str($diff->s === 0 ? 'just now' : $diff->s . ' seconds ago');
    }

    /**
     * Get human-readable time until (e.g., "in 2 hours", "in 3 days")
     */
    public function until(): Str
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->value);

        if ($diff->y > 0) {
            return new Str($diff->y === 1 ? 'in 1 year' : sprintf('in %d years', $diff->y));
        }

        if ($diff->m > 0) {
            return new Str($diff->m === 1 ? 'in 1 month' : sprintf('in %d months', $diff->m));
        }

        if ($diff->d > 0) {
            if ($diff->d >= 7) {
                $weeks = (int) floor($diff->d / 7);
                return new Str($weeks === 1 ? 'in 1 week' : sprintf('in %s weeks', $weeks));
            }

            return new Str($diff->d === 1 ? 'in 1 day' : sprintf('in %d days', $diff->d));
        }

        if ($diff->h > 0) {
            return new Str($diff->h === 1 ? 'in 1 hour' : sprintf('in %d hours', $diff->h));
        }

        if ($diff->i > 0) {
            return new Str($diff->i === 1 ? 'in 1 minute' : sprintf('in %d minutes', $diff->i));
        }

        return new Str($diff->s === 0 ? 'just now' : sprintf('in %d seconds', $diff->s));
    }

    /**
     * Get human-readable difference (automatically chooses ago or until)
     */
    public function diffForHumans(): Str
    {
        return $this->isPast() ? $this->ago() : $this->until();
    }

    // ==================== Getters ====================

    /**
     * Get the year
     */
    public function year(): int
    {
        return (int) $this->value->format('Y');
    }

    /**
     * Get the month (1-12)
     */
    public function month(): int
    {
        return (int) $this->value->format('n');
    }

    /**
     * Get the day (1-31)
     */
    public function day(): int
    {
        return (int) $this->value->format('j');
    }

    /**
     * Get the hour (0-23)
     */
    public function hour(): int
    {
        return (int) $this->value->format('G');
    }

    /**
     * Get the minute (0-59)
     */
    public function minute(): int
    {
        return (int) $this->value->format('i');
    }

    /**
     * Get the second (0-59)
     */
    public function second(): int
    {
        return (int) $this->value->format('s');
    }

    /**
     * Get the microsecond
     */
    public function microsecond(): int
    {
        return (int) $this->value->format('u');
    }

    /**
     * Get the day of week (1=Monday, 7=Sunday)
     */
    public function dayOfWeek(): int
    {
        return (int) $this->value->format('N');
    }

    /**
     * Get the day of year (0-365)
     */
    public function dayOfYear(): int
    {
        return (int) $this->value->format('z');
    }

    /**
     * Get the week of year
     */
    public function weekOfYear(): int
    {
        return (int) $this->value->format('W');
    }

    /**
     * Get the number of days in month
     */
    public function daysInMonth(): int
    {
        return (int) $this->value->format('t');
    }

    /**
     * Get the quarter (1-4)
     */
    public function quarter(): int
    {
        return (int) ceil($this->month() / 3);
    }

    // ==================== Utility ====================

    /**
     * Convert to array
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     day: int,
     *     hour: int,
     *     minute: int,
     *     second: int,
     *     microsecond: int,
     *     timestamp: int,
     *     timezone: string
     * }
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year(),
            'month' => $this->month(),
            'day' => $this->day(),
            'hour' => $this->hour(),
            'minute' => $this->minute(),
            'second' => $this->second(),
            'microsecond' => $this->microsecond(),
            'timestamp' => $this->timestamp(),
            'timezone' => $this->timezoneName()->get(),
        ];
    }

    // ==================== ISO Date ====================

    /**
     * Set ISO date (year, week, day of week)
     */
    public function setISODate(int $year, int $week, int $dayOfWeek = 1): self
    {
        return new self($this->value->setISODate($year, $week, $dayOfWeek));
    }

    // ==================== Sun Info ====================

    /**
     * Get sunrise time for this date
     *
     * @param float $latitude Latitude in degrees
     * @param float $longitude Longitude in degrees
     */
    public function sunrise(float $latitude, float $longitude): ?self
    {
        $timestamp = date_sunrise(
            $this->value->getTimestamp(),
            \SUNFUNCS_RET_TIMESTAMP,
            $latitude,
            $longitude,
            90.833333,
            $this->timezoneOffset() / 3600
        );

        if ($timestamp === false) {
            return null;
        }

        return new self((int) $timestamp, $this->value->getTimezone());
    }

    /**
     * Get sunset time for this date
     *
     * @param float $latitude Latitude in degrees
     * @param float $longitude Longitude in degrees
     */
    public function sunset(float $latitude, float $longitude): ?self
    {
        $timestamp = date_sunset(
            $this->value->getTimestamp(),
            \SUNFUNCS_RET_TIMESTAMP,
            $latitude,
            $longitude,
            90.833333,
            $this->timezoneOffset() / 3600
        );

        if ($timestamp === false) {
            return null;
        }

        return new self((int) $timestamp, $this->value->getTimezone());
    }

    /**
     * Get sun information (sunrise, sunset, transit, etc.)
     *
     * @param float $latitude Latitude in degrees
     * @param float $longitude Longitude in degrees
     * @return array{
     *     sunrise: int,
     *     sunset: int,
     *     transit: int,
     *     civil_twilight_begin: int,
     *     civil_twilight_end: int,
     *     nautical_twilight_begin: int,
     *     nautical_twilight_end: int,
     *     astronomical_twilight_begin: int,
     *     astronomical_twilight_end: int
     * }
     */
    public function sunInfo(float $latitude, float $longitude): array
    {
        return date_sun_info($this->value->getTimestamp(), $latitude, $longitude);
    }

    // ==================== Leap Year ====================

    /**
     * Check if is a leap year
     */
    public function isLeapYear(): bool
    {
        $year = $this->year();
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    // ==================== Helper Methods ====================

    /**
     * Helper to convert various datetime types to DateTimeImmutable
     */
    private function toDateTimeImmutable(self|string|\DateTimeInterface $datetime): \DateTimeImmutable
    {
        return match (true) {
            $datetime instanceof self => $datetime->value,
            $datetime instanceof \DateTimeImmutable => $datetime,
            $datetime instanceof \DateTime => \DateTimeImmutable::createFromMutable($datetime),
            default => new \DateTimeImmutable($datetime),
        };
    }
}
