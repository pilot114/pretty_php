<?php

declare(strict_types=1);

namespace PrettyPhp\System;

/**
 * POSIX process management
 */
readonly class PosixProcess
{
    /**
     * Get current process ID
     */
    public static function id(): int
    {
        return posix_getpid();
    }

    /**
     * Get parent process ID
     */
    public static function parentId(): int
    {
        return posix_getppid();
    }

    /**
     * Get process group ID
     * @throws \RuntimeException
     */
    public static function groupId(?int $pid = null): int
    {
        $result = posix_getpgid($pid ?? self::id());
        if ($result === false) {
            throw new \RuntimeException("Failed to get process group ID");
        }

        return $result;
    }

    /**
     * Get process group ID (legacy)
     */
    public static function pgrp(): int
    {
        return posix_getpgrp();
    }

    /**
     * Get session ID
     * @throws \RuntimeException
     */
    public static function sessionId(?int $pid = null): int
    {
        $result = posix_getsid($pid ?? self::id());
        if ($result === false) {
            throw new \RuntimeException("Failed to get session ID");
        }

        return $result;
    }

    /**
     * Set process group ID
     * @throws \RuntimeException
     */
    public static function setGroupId(int $pid, int $pgid): bool
    {
        $result = posix_setpgid($pid, $pgid);
        if (!$result) {
            throw new \RuntimeException("Failed to set process group ID");
        }

        return true;
    }

    /**
     * Make current process a session leader
     * @throws \RuntimeException
     */
    public static function setSid(): int
    {
        $result = posix_setsid();
        if ($result === -1) {
            throw new \RuntimeException("Failed to set session ID");
        }

        return $result;
    }

    /**
     * Send a signal to a process
     * @throws \RuntimeException
     */
    public static function kill(int $pid, int $signal = 15): bool
    {
        $result = posix_kill($pid, $signal);
        if (!$result) {
            throw new \RuntimeException(sprintf('Failed to send signal %d to process %d', $signal, $pid));
        }

        return true;
    }

    /**
     * Get process times
     * @throws \RuntimeException
     */
    public static function times(): ProcessTimes
    {
        return ProcessTimes::get();
    }
}
