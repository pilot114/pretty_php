<?php

declare(strict_types=1);

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\DateTime;
use PrettyPhp\Base\File;
use PrettyPhp\Base\Json;
use PrettyPhp\Base\Num;
use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;
use PrettyPhp\Functional\Option;
use PrettyPhp\Functional\Result;

if (!function_exists('str')) {
    function str(string $value): Str
    {
        return new Str($value);
    }
}

if (!function_exists('arr')) {
    /**
     * @template T
     * @param iterable<int|string, T> $value
     * @return Arr<T>
     */
    function arr(iterable $value = []): Arr
    {
        return new Arr($value);
    }
}

if (!function_exists('file')) {
    function file(string $path): File
    {
        return new File($path);
    }
}

if (!function_exists('path')) {
    function path(string $path): Path
    {
        return new Path($path);
    }
}

if (!function_exists('some')) {
    /**
     * Creates an Option with a value (Some).
     *
     * @template T
     * @param T $value
     * @return Option<T>
     */
    function some(mixed $value): Option
    {
        return Option::some($value);
    }
}

if (!function_exists('none')) {
    /**
     * Creates an Option without a value (None).
     *
     * @return Option<never>
     */
    function none(): Option
    {
        return Option::none();
    }
}

if (!function_exists('ok')) {
    /**
     * Creates a success Result with a value.
     *
     * @template T
     * @param T $value
     * @return Result<T, never>
     */
    function ok(mixed $value): Result
    {
        return Result::ok($value);
    }
}

if (!function_exists('err')) {
    /**
     * Creates an error Result with an error value.
     *
     * @template E
     * @param E $error
     * @return Result<never, E>
     */
    function err(mixed $error): Result
    {
        return Result::err($error);
    }
}

if (!function_exists('num')) {
    function num(int|float $value): Num
    {
        return new Num($value);
    }
}

if (!function_exists('json')) {
    /**
     * Creates a Json instance from a string or data.
     *
     * @param string|mixed $value JSON string or data to encode
     */
    function json(mixed $value): Json
    {
        if (is_string($value)) {
            return Json::fromString($value);
        }

        return Json::fromData($value);
    }
}

if (!function_exists('datetime')) {
    /**
     * Creates a DateTime instance.
     *
     * @param \DateTimeImmutable|string|int|null $value DateTimeImmutable, string, timestamp, or null for now
     * @param \DateTimeZone|string|null $timezone Timezone
     */
    function datetime(
        \DateTimeImmutable|string|int|null $value = null,
        \DateTimeZone|string|null $timezone = null
    ): DateTime {
        return new DateTime($value, $timezone);
    }
}
