<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Shared \DateTimeZone instances by name.
 *
 * \DateTimeZone has no mutators, so one instance per identifier can be reused; creating it
 * parses the timezone database entry every time, which is measurable in hot paths.
 * Lives outside the readonly value classes because they cannot declare static properties.
 *
 * @internal
 */
final class TimezoneCache
{
    /** @var array<string, \DateTimeZone> */
    private static array $zones = [];

    public static function get(string $name): \DateTimeZone
    {
        return self::$zones[$name] ??= new \DateTimeZone($name);
    }
}
