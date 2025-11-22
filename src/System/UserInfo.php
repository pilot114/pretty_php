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
            throw new \RuntimeException('User not found: ' . $username);
        }

        /** @var string $name */
        $name = $info['name'];
        /** @var string $passwd */
        $passwd = $info['passwd'];
        /** @var int $uid */
        $uid = $info['uid'];
        /** @var int $gid */
        $gid = $info['gid'];
        /** @var string $gecos */
        $gecos = $info['gecos'];
        /** @var string $dir */
        $dir = $info['dir'];
        /** @var string $shell */
        $shell = $info['shell'];

        return new self($name, $passwd, $uid, $gid, $gecos, $dir, $shell);
    }

    /**
     * Get user by UID
     * @throws \RuntimeException
     */
    public static function fromUid(int $uid): self
    {
        $info = posix_getpwuid($uid);
        if ($info === false) {
            throw new \RuntimeException(sprintf('User with UID %d not found', $uid));
        }

        /** @var string $name */
        $name = $info['name'];
        /** @var string $passwd */
        $passwd = $info['passwd'];
        /** @var int $uidValue */
        $uidValue = $info['uid'];
        /** @var int $gid */
        $gid = $info['gid'];
        /** @var string $gecos */
        $gecos = $info['gecos'];
        /** @var string $dir */
        $dir = $info['dir'];
        /** @var string $shell */
        $shell = $info['shell'];

        return new self($name, $passwd, $uidValue, $gid, $gecos, $dir, $shell);
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
