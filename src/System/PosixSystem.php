<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Str;

/**
 * POSIX system information and configuration
 */
readonly class PosixSystem
{
    /**
     * Get system information
     * @throws \RuntimeException
     */
    public static function uname(): SystemInfo
    {
        return SystemInfo::get();
    }

    /**
     * Get system configuration value
     * @throws \RuntimeException
     */
    public static function sysconf(int $name): int
    {
        $result = posix_sysconf($name);
        if ($result === false) {
            throw new \RuntimeException("Failed to get system configuration");
        }

        return $result;
    }

    /**
     * Get current working directory
     * @throws \RuntimeException
     */
    public static function getcwd(): Str
    {
        $cwd = posix_getcwd();
        if ($cwd === false) {
            throw new \RuntimeException("Failed to get current working directory");
        }

        return new Str($cwd);
    }

    /**
     * Get controlling terminal name
     * @throws \RuntimeException
     */
    public static function ctermid(): Str
    {
        $term = posix_ctermid();
        if ($term === false) {
            throw new \RuntimeException("Failed to get controlling terminal name");
        }

        return new Str($term);
    }

    /**
     * Get last POSIX error number
     */
    public static function errno(): int
    {
        return posix_get_last_error();
    }

    /**
     * Get last POSIX error number (alias)
     */
    public static function getLastError(): int
    {
        return posix_get_last_error();
    }

    /**
     * Get error message string
     */
    public static function strerror(int $errno): Str
    {
        return new Str(posix_strerror($errno));
    }

    /**
     * Get last error message
     */
    public static function getLastErrorMessage(): Str
    {
        return self::strerror(self::getLastError());
    }

    /**
     * Get resource limit
     * @throws \RuntimeException
     */
    public static function getResourceLimit(int $resource): ResourceLimit
    {
        return ResourceLimit::get($resource);
    }

    /**
     * Set resource limit
     * @throws \RuntimeException
     */
    public static function setResourceLimit(int $resource, int $softLimit, int $hardLimit): bool
    {
        return ResourceLimit::set($resource, $softLimit, $hardLimit);
    }
}
