<?php

declare(strict_types=1);

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\File;
use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;

if (!function_exists('str')) {
    function str(string $value): Str
    {
        return new Str($value);
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
