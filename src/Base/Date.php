<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Static utility class for date/time operations
 * Provides functional interface to all PHP date functions
 */
final class Date
{
    // Prevent instantiation
    private function __construct()
    {
    }

    // ==================== Current Time ====================

    /**
     * Get current Unix timestamp
     * PHP: time()
     */
    public static function time(): int
    {
        return time();
    }

    /**
     * Get current datetime
     * PHP: date_create() / new DateTime('now')
     */
    public static function now(Timezone|\DateTimeZone|string|null $timezone = null): DateTime
    {
        return DateTime::now($timezone);
    }

    // ==================== Validation ====================

    /**
     * Validate a Gregorian date
     * PHP: checkdate()
     */
    public static function checkdate(int $month, int $day, int $year): bool
    {
        return checkdate($month, $day, $year);
    }

    /**
     * Check if date is valid
     */
    public static function isValid(int $year, int $month, int $day): bool
    {
        return checkdate($month, $day, $year);
    }

    // ==================== Formatting ====================

    /**
     * Format a Unix timestamp
     * PHP: date()
     */
    public static function format(string $format, ?int $timestamp = null): Str
    {
        return new Str(date($format, $timestamp ?? time()));
    }

    /**
     * Format a Unix timestamp as GMT/UTC
     * PHP: gmdate()
     */
    public static function gmdate(string $format, ?int $timestamp = null): Str
    {
        return new Str(gmdate($format, $timestamp ?? time()));
    }

    /**
     * Format timestamp as integer
     * PHP: idate()
     */
    public static function idate(string $format, ?int $timestamp = null): int
    {
        return idate($format, $timestamp ?? time());
    }

    // ==================== Parsing ====================

    /**
     * Parse a date string into components
     * PHP: date_parse()
     *
     * @return array{
     *     year: int|false,
     *     month: int|false,
     *     day: int|false,
     *     hour: int|false,
     *     minute: int|false,
     *     second: int|false,
     *     fraction: float|false,
     *     warning_count: int,
     *     warnings: array<int, string>,
     *     error_count: int,
     *     errors: array<int, string>,
     *     is_localtime: bool,
     *     zone_type?: int,
     *     zone?: int,
     *     is_dst?: bool,
     *     tz_abbr?: string,
     *     tz_id?: string
     * }
     */
    public static function parse(string $datetime): array
    {
        return date_parse($datetime);
    }

    /**
     * Parse a date string according to a specified format
     * PHP: date_parse_from_format()
     *
     * @return array{
     *     year: int|false,
     *     month: int|false,
     *     day: int|false,
     *     hour: int|false,
     *     minute: int|false,
     *     second: int|false,
     *     fraction: float|false,
     *     warning_count: int,
     *     warnings: array<int, string>,
     *     error_count: int,
     *     errors: array<int, string>,
     *     is_localtime: bool
     * }
     */
    public static function parseFromFormat(string $format, string $datetime): array
    {
        return date_parse_from_format($format, $datetime);
    }

    /**
     * Parse a string into a Unix timestamp
     * PHP: strtotime()
     */
    public static function strtotime(string $datetime, ?int $baseTimestamp = null): int|false
    {
        return strtotime($datetime, $baseTimestamp ?? time());
    }

    /**
     * Get last errors from date parsing
     * PHP: date_get_last_errors()
     *
     * @return array{
     *     warning_count: int,
     *     warnings: array<int, string>,
     *     error_count: int,
     *     errors: array<int, string>
     * }|false
     */
    public static function getLastErrors(): array|false
    {
        return \DateTime::getLastErrors();
    }

    // ==================== Timestamp Creation ====================

    /**
     * Get Unix timestamp for a date (local time)
     * PHP: mktime()
     */
    public static function mktime(
        int $hour = 0,
        ?int $minute = null,
        ?int $second = null,
        ?int $month = null,
        ?int $day = null,
        ?int $year = null
    ): int|false {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Get Unix timestamp for a date (GMT)
     * PHP: gmmktime()
     */
    public static function gmmktime(
        int $hour = 0,
        ?int $minute = null,
        ?int $second = null,
        ?int $month = null,
        ?int $day = null,
        ?int $year = null
    ): int|false {
        return gmmktime($hour, $minute, $second, $month, $day, $year);
    }

    // ==================== Date Information ====================

    /**
     * Get date/time information
     * PHP: getdate()
     *
     * @return array{
     *     seconds: int,
     *     minutes: int,
     *     hours: int,
     *     mday: int,
     *     wday: int,
     *     mon: int,
     *     year: int,
     *     yday: int,
     *     weekday: string,
     *     month: string,
     *     0: int
     * }
     */
    public static function getdate(?int $timestamp = null): array
    {
        return getdate($timestamp ?? time());
    }

    /**
     * Get local time
     * PHP: localtime()
     *
     * @return array<int>|array{
     *     tm_sec: int,
     *     tm_min: int,
     *     tm_hour: int,
     *     tm_mday: int,
     *     tm_mon: int,
     *     tm_year: int,
     *     tm_wday: int,
     *     tm_yday: int,
     *     tm_isdst: int
     * }
     */
    public static function localtime(?int $timestamp = null, bool $associative = false): array
    {
        return localtime($timestamp ?? time(), $associative);
    }

    // ==================== Timezone ====================

    /**
     * Get default timezone
     * PHP: date_default_timezone_get()
     */
    public static function getDefaultTimezone(): Str
    {
        return new Str(date_default_timezone_get());
    }

    /**
     * Set default timezone
     * PHP: date_default_timezone_set()
     */
    public static function setDefaultTimezone(string $timezoneId): bool
    {
        return date_default_timezone_set($timezoneId);
    }

    /**
     * Get timezone database version
     * PHP: timezone_version_get()
     */
    public static function timezoneVersion(): Str
    {
        return new Str(timezone_version_get());
    }

    // ==================== Sun Times ====================

    /**
     * Get sunrise time for a date and location
     * PHP: date_sunrise()
     */
    public static function sunrise(
        int $timestamp,
        int $returnFormat = \SUNFUNCS_RET_STRING,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $zenith = null,
        ?float $utcOffset = null
    ): string|int|float|false {
        return date_sunrise($timestamp, $returnFormat, $latitude, $longitude, $zenith, $utcOffset);
    }

    /**
     * Get sunset time for a date and location
     * PHP: date_sunset()
     */
    public static function sunset(
        int $timestamp,
        int $returnFormat = \SUNFUNCS_RET_STRING,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $zenith = null,
        ?float $utcOffset = null
    ): string|int|float|false {
        return date_sunset($timestamp, $returnFormat, $latitude, $longitude, $zenith, $utcOffset);
    }

    /**
     * Get sun information for a date and location
     * PHP: date_sun_info()
     *
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
    public static function sunInfo(int $timestamp, float $latitude, float $longitude): array
    {
        return date_sun_info($timestamp, $latitude, $longitude);
    }

    // ==================== Factory Methods ====================

    /**
     * Create DateTime from string
     * PHP: date_create()
     */
    public static function create(
        string $datetime = 'now',
        Timezone|\DateTimeZone|string|null $timezone = null
    ): DateTime {
        return new DateTime($datetime, $timezone);
    }

    /**
     * Create DateTime from format
     * PHP: date_create_from_format()
     */
    public static function createFromFormat(
        string $format,
        string $datetime,
        Timezone|\DateTimeZone|string|null $timezone = null
    ): DateTime {
        return DateTime::fromFormat($format, $datetime, $timezone);
    }

    /**
     * Create DateTime from timestamp
     * PHP: @timestamp format
     */
    public static function createFromTimestamp(
        int $timestamp,
        Timezone|\DateTimeZone|string|null $timezone = null
    ): DateTime {
        return DateTime::fromTimestamp($timestamp, $timezone);
    }

    // ==================== Quick Helpers ====================

    /**
     * Get current year
     */
    public static function year(): int
    {
        return (int) date('Y');
    }

    /**
     * Get current month (1-12)
     */
    public static function month(): int
    {
        return (int) date('n');
    }

    /**
     * Get current day (1-31)
     */
    public static function day(): int
    {
        return (int) date('j');
    }

    /**
     * Get current hour (0-23)
     */
    public static function hour(): int
    {
        return (int) date('G');
    }

    /**
     * Get current minute (0-59)
     */
    public static function minute(): int
    {
        return (int) date('i');
    }

    /**
     * Get current second (0-59)
     */
    public static function second(): int
    {
        return (int) date('s');
    }

    /**
     * Get today at midnight
     */
    public static function today(Timezone|\DateTimeZone|string|null $timezone = null): DateTime
    {
        return DateTime::today($timezone);
    }

    /**
     * Get yesterday at midnight
     */
    public static function yesterday(Timezone|\DateTimeZone|string|null $timezone = null): DateTime
    {
        return DateTime::yesterday($timezone);
    }

    /**
     * Get tomorrow at midnight
     */
    public static function tomorrow(Timezone|\DateTimeZone|string|null $timezone = null): DateTime
    {
        return DateTime::tomorrow($timezone);
    }

    // ==================== Constants ====================

    /**
     * Common date format constants
     */
    public const ATOM = \DateTimeInterface::ATOM;
    public const COOKIE = \DateTimeInterface::COOKIE;
    public const ISO8601 = \DateTimeInterface::ISO8601;
    public const RFC822 = \DateTimeInterface::RFC822;
    public const RFC850 = \DateTimeInterface::RFC850;
    public const RFC1036 = \DateTimeInterface::RFC1036;
    public const RFC1123 = \DateTimeInterface::RFC1123;
    public const RFC2822 = \DateTimeInterface::RFC2822;
    public const RFC3339 = \DateTimeInterface::RFC3339;
    public const RFC3339_EXTENDED = \DateTimeInterface::RFC3339_EXTENDED;
    public const RFC7231 = \DateTimeInterface::RFC7231;
    public const RSS = \DateTimeInterface::RSS;
    public const W3C = \DateTimeInterface::W3C;
}
