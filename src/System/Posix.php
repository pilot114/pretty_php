<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Str;

/**
 * Elegant OOP wrapper for POSIX functions
 *
 * This class provides a clean, object-oriented interface to all POSIX functions.
 * Functions are organized into logical groups for better discoverability.
 *
 * @example
 * // Get current user information
 * $user = Posix::user()->current();
 * echo $user->name;
 *
 * // Get process information
 * $pid = Posix::process()->id();
 * $ppid = Posix::process()->parentId();
 *
 * // Check file access
 * if (Posix::file()->isReadable('/etc/passwd')) {
 *     echo "Can read /etc/passwd";
 * }
 *
 * // Get system information
 * $sysinfo = Posix::system()->uname();
 * echo $sysinfo->sysname; // Linux
 */
final readonly class Posix
{
    /**
     * Process management functions
     */
    public static function process(): PosixProcess
    {
        return new PosixProcess();
    }

    /**
     * User and group management functions
     */
    public static function user(): PosixUser
    {
        return new PosixUser();
    }

    /**
     * File system functions
     */
    public static function file(): PosixFile
    {
        return new PosixFile();
    }

    /**
     * System information and configuration functions
     */
    public static function system(): PosixSystem
    {
        return new PosixSystem();
    }

    // Convenience shortcuts for commonly used functions

    /**
     * Get current process ID
     */
    public static function pid(): int
    {
        return PosixProcess::id();
    }

    /**
     * Get current user ID
     */
    public static function uid(): int
    {
        return PosixUser::uid();
    }

    /**
     * Get current group ID
     */
    public static function gid(): int
    {
        return PosixUser::gid();
    }

    /**
     * Get current working directory
     * @throws \RuntimeException
     */
    public static function getcwd(): Str
    {
        return PosixSystem::getcwd();
    }

    /**
     * Get system information
     * @throws \RuntimeException
     */
    public static function uname(): SystemInfo
    {
        return SystemInfo::get();
    }

    /**
     * Get current user information
     * @throws \RuntimeException
     */
    public static function getCurrentUser(): UserInfo
    {
        return UserInfo::current();
    }

    /**
     * Get current group information
     * @throws \RuntimeException
     */
    public static function getCurrentGroup(): GroupInfo
    {
        return GroupInfo::current();
    }

    /**
     * Send a signal to a process
     * @throws \RuntimeException
     */
    public static function kill(int $pid, int $signal = 15): bool
    {
        return PosixProcess::kill($pid, $signal);
    }

    /**
     * Check if file is accessible
     * @throws \RuntimeException
     */
    public static function access(string $path, int $mode = PosixFile::F_OK): bool
    {
        return PosixFile::access($path, $mode);
    }

    /**
     * Get last POSIX error
     */
    public static function getLastError(): int
    {
        return PosixSystem::getLastError();
    }

    /**
     * Get last POSIX error message
     */
    public static function getLastErrorMessage(): Str
    {
        return PosixSystem::getLastErrorMessage();
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

    /**
     * Get process times
     * @throws \RuntimeException
     */
    public static function times(): ProcessTimes
    {
        return ProcessTimes::get();
    }

    /**
     * Check if running as root
     */
    public static function isRoot(): bool
    {
        return self::uid() === 0;
    }

    /**
     * Check if file descriptor is a terminal
     * @param int|resource $fd
     */
    public static function isatty(mixed $fd): bool
    {
        return PosixFile::isatty($fd);
    }

    /**
     * Check if running in a terminal
     */
    public static function isTTY(): bool
    {
        return self::isatty(\STDOUT);
    }

    /**
     * Get login name
     * @throws \RuntimeException
     */
    public static function getLogin(): string
    {
        return PosixUser::getLogin();
    }

    /**
     * Get supplementary group IDs
     * @return Arr<int>
     * @throws \RuntimeException
     */
    public static function getGroups(): Arr
    {
        return PosixUser::getGroups();
    }
}
