<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

readonly class Timezone implements \Stringable
{
    private \DateTimeZone $value;

    /**
     * @param \DateTimeZone|string $value DateTimeZone or timezone identifier (e.g., 'UTC', 'Europe/Paris')
     */
    public function __construct(\DateTimeZone|string $value)
    {
        $this->value = match (true) {
            $value instanceof \DateTimeZone => $value,
            default => TimezoneCache::get($value),
        };
    }

    /**
     * Create from DateTimeZone
     */
    #[\NoDiscard]
    public static function fromTimezone(\DateTimeZone $timezone): self
    {
        return new self($timezone);
    }

    /**
     * Create from timezone identifier
     */
    #[\NoDiscard]
    public static function fromIdentifier(string $identifier): self
    {
        return new self($identifier);
    }

    /**
     * Create from timezone abbreviation
     */
    #[\NoDiscard]
    public static function fromAbbreviation(string $abbr, ?int $utcOffset = null, ?bool $isDst = null): ?self
    {
        $identifier = timezone_name_from_abbr($abbr, $utcOffset ?? -1, $isDst === null ? -1 : ($isDst ? 1 : 0));
        if ($identifier === false) {
            return null;
        }

        return new self($identifier);
    }

    /**
     * Get UTC timezone
     */
    #[\NoDiscard]
    public static function utc(): self
    {
        return new self('UTC');
    }

    /**
     * Get the underlying DateTimeZone
     */
    public function get(): \DateTimeZone
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value->getName();
    }

    // ==================== Information ====================

    /**
     * Get timezone name/identifier
     */
    #[\NoDiscard]
    public function name(): Str
    {
        return new Str($this->value->getName());
    }

    /**
     * Get timezone location information
     *
     * @return array{
     *     country_code: string,
     *     latitude: float,
     *     longitude: float,
     *     comments: string
     * }|false
     */
    public function location(): array|false
    {
        return $this->value->getLocation();
    }

    /**
     * Get offset from UTC for a specific date
     */
    public function offset(\DateTimeInterface|null $datetime = null): int
    {
        $dt = $datetime ?? new \DateTimeImmutable('now', $this->value);
        return $this->value->getOffset($dt);
    }

    /**
     * Get offset in hours
     */
    public function offsetHours(\DateTimeInterface|null $datetime = null): float
    {
        return $this->offset($datetime) / 3600;
    }

    /**
     * Get offset as formatted string (e.g., '+03:00', '-05:00')
     */
    #[\NoDiscard]
    public function offsetString(\DateTimeInterface|null $datetime = null): Str
    {
        $moment = \DateTimeImmutable::createFromInterface($datetime ?? new \DateTimeImmutable());

        return new Str($moment->setTimezone($this->value)->format('P'));
    }

    /**
     * Get all transitions for this timezone
     *
     * @param int|null $timestampBegin Start timestamp
     * @param int|null $timestampEnd End timestamp
     * @return array<array{
     *     ts: int,
     *     time: string,
     *     offset: int,
     *     isdst: bool,
     *     abbr: string
     * }>
     */
    public function transitions(?int $timestampBegin = null, ?int $timestampEnd = null): array
    {
        if ($timestampBegin !== null && $timestampEnd !== null) {
            return $this->value->getTransitions($timestampBegin, $timestampEnd);
        }

        if ($timestampBegin !== null) {
            return $this->value->getTransitions($timestampBegin);
        }

        return $this->value->getTransitions();
    }

    // ==================== Comparison ====================

    /**
     * Check if equals to another timezone
     */
    public function equals(self|string|\DateTimeZone $other): bool
    {
        $otherTz = $this->toDateTimeZone($other);
        return $this->value->getName() === $otherTz->getName();
    }

    // ==================== Static Lists ====================

    /**
     * Get list of all timezone identifiers
     *
     * @param int $timezoneGroup DateTimeZone constant (AFRICA, AMERICA, ANTARCTICA, ARCTIC, ASIA, ATLANTIC,
     *                           AUSTRALIA, EUROPE, INDIAN, PACIFIC, UTC, ALL, ALL_WITH_BC, PER_COUNTRY)
     * @param string|null $countryCode Two-letter ISO 3166-1 country code
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function identifiers(int $timezoneGroup = \DateTimeZone::ALL, ?string $countryCode = null): Arr
    {
        return new Arr(
            \DateTimeZone::listIdentifiers($timezoneGroup, $countryCode)
        );
    }

    /**
     * Get list of all timezone abbreviations
     *
     * @return array<string, array<array{
     *     dst: bool,
     *     offset: int,
     *     timezone_id: string|null
     * }>>
     */
    public static function abbreviations(): array
    {
        return \DateTimeZone::listAbbreviations();
    }

    // ==================== Constants ====================

    /**
     * Get Africa timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function africa(): Arr
    {
        return self::identifiers(\DateTimeZone::AFRICA);
    }

    /**
     * Get America timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function america(): Arr
    {
        return self::identifiers(\DateTimeZone::AMERICA);
    }

    /**
     * Get Antarctica timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function antarctica(): Arr
    {
        return self::identifiers(\DateTimeZone::ANTARCTICA);
    }

    /**
     * Get Arctic timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function arctic(): Arr
    {
        return self::identifiers(\DateTimeZone::ARCTIC);
    }

    /**
     * Get Asia timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function asia(): Arr
    {
        return self::identifiers(\DateTimeZone::ASIA);
    }

    /**
     * Get Atlantic timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function atlantic(): Arr
    {
        return self::identifiers(\DateTimeZone::ATLANTIC);
    }

    /**
     * Get Australia timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function australia(): Arr
    {
        return self::identifiers(\DateTimeZone::AUSTRALIA);
    }

    /**
     * Get Europe timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function europe(): Arr
    {
        return self::identifiers(\DateTimeZone::EUROPE);
    }

    /**
     * Get Indian timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function indian(): Arr
    {
        return self::identifiers(\DateTimeZone::INDIAN);
    }

    /**
     * Get Pacific timezones
     *
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function pacific(): Arr
    {
        return self::identifiers(\DateTimeZone::PACIFIC);
    }

    /**
     * Get timezones for a specific country
     *
     * @param string $countryCode Two-letter ISO 3166-1 country code
     * @return Arr<string>
     */
    #[\NoDiscard]
    public static function forCountry(string $countryCode): Arr
    {
        return self::identifiers(\DateTimeZone::PER_COUNTRY, $countryCode);
    }

    // ==================== Utility ====================

    /**
     * Convert to array
     *
     * @return array{
     *     name: string,
     *     offset: int,
     *     offset_string: string,
     *     location: array{
     *         country_code: string,
     *         latitude: float,
     *         longitude: float,
     *         comments: string
     *     }|false
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name()->get(),
            'offset' => $this->offset(),
            'offset_string' => $this->offsetString()->get(),
            'location' => $this->location(),
        ];
    }

    /**
     * Helper to convert various timezone types to DateTimeZone
     */
    private function toDateTimeZone(self|string|\DateTimeZone $timezone): \DateTimeZone
    {
        return match (true) {
            $timezone instanceof self => $timezone->value,
            $timezone instanceof \DateTimeZone => $timezone,
            default => TimezoneCache::get($timezone),
        };
    }
}
