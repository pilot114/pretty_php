<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Str;

/**
 * POSIX file system operations
 */
readonly class PosixFile
{
    // Access modes
    public const F_OK = 0;  // File exists
    public const R_OK = 4;  // Read permission
    public const W_OK = 2;  // Write permission
    public const X_OK = 1;  // Execute permission

    // File types for mknod
    public const S_IFREG = 0100000;  // Regular file
    public const S_IFCHR = 0020000;  // Character device
    public const S_IFBLK = 0060000;  // Block device
    public const S_IFIFO = 0010000;  // FIFO (named pipe)
    public const S_IFSOCK = 0140000; // Socket

    /**
     * Check file accessibility
     * @throws \RuntimeException
     */
    public static function access(string $path, int $mode = self::F_OK): bool
    {
        return posix_access($path, $mode);
    }

    /**
     * Check file accessibility using effective user/group IDs
     * @throws \RuntimeException
     */
    public static function eaccess(string $path, int $mode = self::F_OK): bool
    {
        if (!function_exists('posix_eaccess')) {
            throw new \RuntimeException("posix_eaccess is not available on this system");
        }

        return posix_eaccess($path, $mode);
    }

    /**
     * Check if file descriptor is a terminal
     */
    public static function isatty(mixed $fd): bool
    {
        return posix_isatty($fd);
    }

    /**
     * Get path configuration value
     * @throws \RuntimeException
     */
    public static function pathconf(string $path, int $name): int|false
    {
        $result = posix_pathconf($path, $name);
        if ($result === false) {
            throw new \RuntimeException("Failed to get path configuration for {$path}");
        }

        return $result;
    }

    /**
     * Get file descriptor configuration value
     * @throws \RuntimeException
     */
    public static function fpathconf(mixed $fd, int $name): int|false
    {
        $result = posix_fpathconf($fd, $name);
        if ($result === false) {
            throw new \RuntimeException("Failed to get file descriptor configuration");
        }

        return $result;
    }

    /**
     * Create a FIFO special file (named pipe)
     * @throws \RuntimeException
     */
    public static function mkfifo(string $path, int $mode = 0666): bool
    {
        $result = posix_mkfifo($path, $mode);
        if (!$result) {
            throw new \RuntimeException("Failed to create FIFO at {$path}");
        }

        return true;
    }

    /**
     * Create a special or ordinary file
     * @throws \RuntimeException
     */
    public static function mknod(string $path, int $mode, int $major = 0, int $minor = 0): bool
    {
        $result = posix_mknod($path, $mode, $major, $minor);
        if (!$result) {
            throw new \RuntimeException("Failed to create node at {$path}");
        }

        return true;
    }

    /**
     * Get terminal name
     * @throws \RuntimeException
     */
    public static function ttyname(mixed $fd): Str
    {
        $name = posix_ttyname($fd);
        if ($name === false) {
            throw new \RuntimeException("Failed to get terminal name");
        }

        return new Str($name);
    }

    /**
     * Check if path is readable
     */
    public static function isReadable(string $path): bool
    {
        return self::access($path, self::R_OK);
    }

    /**
     * Check if path is writable
     */
    public static function isWritable(string $path): bool
    {
        return self::access($path, self::W_OK);
    }

    /**
     * Check if path is executable
     */
    public static function isExecutable(string $path): bool
    {
        return self::access($path, self::X_OK);
    }

    /**
     * Check if path exists
     */
    public static function exists(string $path): bool
    {
        return self::access($path, self::F_OK);
    }
}
