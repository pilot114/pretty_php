<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Str;

/**
 * Value object representing user information from POSIX
 */
readonly class UserInfo
{
    public function __construct(
        public string $name,
        public string $password,
        public int $uid,
        public int $gid,
        public string $gecos,
        public string $dir,
        public string $shell
    ) {
    }

    /**
     * Get user by name
     * @throws \RuntimeException
     */
    public static function fromName(string $username): self
    {
        $info = posix_getpwnam($username);
        if ($info === false) {
            throw new \RuntimeException("User not found: {$username}");
        }

        return new self(
            $info['name'],
            $info['passwd'],
            $info['uid'],
            $info['gid'],
            $info['gecos'],
            $info['dir'],
            $info['shell']
        );
    }

    /**
     * Get user by UID
     * @throws \RuntimeException
     */
    public static function fromUid(int $uid): self
    {
        $info = posix_getpwuid($uid);
        if ($info === false) {
            throw new \RuntimeException("User with UID {$uid} not found");
        }

        return new self(
            $info['name'],
            $info['passwd'],
            $info['uid'],
            $info['gid'],
            $info['gecos'],
            $info['dir'],
            $info['shell']
        );
    }

    /**
     * Get current user
     * @throws \RuntimeException
     */
    public static function current(): self
    {
        return self::fromUid(posix_getuid());
    }

    /**
     * Get effective user
     * @throws \RuntimeException
     */
    public static function effective(): self
    {
        return self::fromUid(posix_geteuid());
    }

    public function getName(): Str
    {
        return new Str($this->name);
    }

    public function getHomeDir(): Str
    {
        return new Str($this->dir);
    }

    public function getShell(): Str
    {
        return new Str($this->shell);
    }

    public function getGecos(): Str
    {
        return new Str($this->gecos);
    }
}
