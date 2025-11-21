<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Arr;

/**
 * POSIX user and group management
 */
readonly class PosixUser
{
    /**
     * Get current user ID
     */
    public static function uid(): int
    {
        return posix_getuid();
    }

    /**
     * Get effective user ID
     */
    public static function euid(): int
    {
        return posix_geteuid();
    }

    /**
     * Get current group ID
     */
    public static function gid(): int
    {
        return posix_getgid();
    }

    /**
     * Get effective group ID
     */
    public static function egid(): int
    {
        return posix_getegid();
    }

    /**
     * Set user ID
     * @throws \RuntimeException
     */
    public static function setUid(int $uid): bool
    {
        $result = posix_setuid($uid);
        if (!$result) {
            throw new \RuntimeException("Failed to set UID to {$uid}");
        }

        return true;
    }

    /**
     * Set effective user ID
     * @throws \RuntimeException
     */
    public static function setEuid(int $uid): bool
    {
        $result = posix_seteuid($uid);
        if (!$result) {
            throw new \RuntimeException("Failed to set effective UID to {$uid}");
        }

        return true;
    }

    /**
     * Set group ID
     * @throws \RuntimeException
     */
    public static function setGid(int $gid): bool
    {
        $result = posix_setgid($gid);
        if (!$result) {
            throw new \RuntimeException("Failed to set GID to {$gid}");
        }

        return true;
    }

    /**
     * Set effective group ID
     * @throws \RuntimeException
     */
    public static function setEgid(int $gid): bool
    {
        $result = posix_setegid($gid);
        if (!$result) {
            throw new \RuntimeException("Failed to set effective GID to {$gid}");
        }

        return true;
    }

    /**
     * Get user information by name
     * @throws \RuntimeException
     */
    public static function getByName(string $username): UserInfo
    {
        return UserInfo::fromName($username);
    }

    /**
     * Get user information by UID
     * @throws \RuntimeException
     */
    public static function getByUid(int $uid): UserInfo
    {
        return UserInfo::fromUid($uid);
    }

    /**
     * Get current user information
     * @throws \RuntimeException
     */
    public static function current(): UserInfo
    {
        return UserInfo::current();
    }

    /**
     * Get effective user information
     * @throws \RuntimeException
     */
    public static function effective(): UserInfo
    {
        return UserInfo::effective();
    }

    /**
     * Get group information by name
     * @throws \RuntimeException
     */
    public static function getGroupByName(string $groupname): GroupInfo
    {
        return GroupInfo::fromName($groupname);
    }

    /**
     * Get group information by GID
     * @throws \RuntimeException
     */
    public static function getGroupByGid(int $gid): GroupInfo
    {
        return GroupInfo::fromGid($gid);
    }

    /**
     * Get current group information
     * @throws \RuntimeException
     */
    public static function currentGroup(): GroupInfo
    {
        return GroupInfo::current();
    }

    /**
     * Get effective group information
     * @throws \RuntimeException
     */
    public static function effectiveGroup(): GroupInfo
    {
        return GroupInfo::effective();
    }

    /**
     * Get supplementary group IDs
     * @return Arr<int>
     * @throws \RuntimeException
     */
    public static function getGroups(): Arr
    {
        $groups = posix_getgroups();
        if ($groups === false) {
            throw new \RuntimeException("Failed to get supplementary groups");
        }

        return new Arr($groups);
    }

    /**
     * Initialize supplementary group access list
     * @throws \RuntimeException
     */
    public static function initGroups(string $username, int $gid): bool
    {
        $result = posix_initgroups($username, $gid);
        if (!$result) {
            throw new \RuntimeException("Failed to initialize groups for user {$username}");
        }

        return true;
    }

    /**
     * Get login name
     * @throws \RuntimeException
     */
    public static function getLogin(): string
    {
        $login = posix_getlogin();
        if ($login === false) {
            throw new \RuntimeException("Failed to get login name");
        }

        return $login;
    }
}
