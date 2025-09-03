<?php

declare(strict_types=1);

use PrettyPhp\PrettyPhp;
use PrettyPhp\Str;
use PrettyPhp\Arr;
use PrettyPhp\File;
use PrettyPhp\Path;

if (!function_exists('str')) {
    function str(string $value): Str
    {
        return PrettyPhp::str($value);
    }
}

if (!function_exists('arr')) {
    /**
     * @template T
     * @param array<int|string, T> $value
     * @return Arr<T>
     */
    function arr(array $value = []): Arr
    {
        return PrettyPhp::arr($value);
    }
}

if (!function_exists('file')) {
    function file(string $path): File
    {
        return PrettyPhp::file($path);
    }
}

if (!function_exists('path')) {
    function path(string $path): Path
    {
        return PrettyPhp::path($path);
    }
}
