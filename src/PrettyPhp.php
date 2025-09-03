<?php

declare(strict_types=1);

namespace PrettyPhp;

final class PrettyPhp
{
    public static function str(string $value): Str
    {
        return new Str($value);
    }

    /**
     * @template T
     * @param array<int|string, T> $value
     * @return Arr<T>
     */
    public static function arr(array $value = []): Arr
    {
        return new Arr($value);
    }

    public static function file(string $path): File
    {
        return new File($path);
    }

    public static function path(string $path): Path
    {
        return new Path($path);
    }
}
