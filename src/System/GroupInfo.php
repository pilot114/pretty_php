<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\Str;

/**
 * Value object representing group information from POSIX
 */
readonly class GroupInfo
{
    /**
     * @param array<string> $members
     */
    public function __construct(
        public string $name,
        public string $password,
        public int $gid,
        public array $members
    ) {
    }

    /**
     * Get group by name
     * @throws \RuntimeException
     */
    public static function fromName(string $groupname): self
    {
        $info = posix_getgrnam($groupname);
        if ($info === false) {
            throw new \RuntimeException('Group not found: ' . $groupname);
        }

        /** @var string $name */
        $name = $info['name'];
        /** @var string $passwd */
        $passwd = $info['passwd'];
        /** @var int $gid */
        $gid = $info['gid'];
        /** @var array<string> $members */
        $members = $info['members'];

        return new self($name, $passwd, $gid, $members);
    }

    /**
     * Get group by GID
     * @throws \RuntimeException
     */
    public static function fromGid(int $gid): self
    {
        $info = posix_getgrgid($gid);
        if ($info === false) {
            throw new \RuntimeException(sprintf('Group with GID %d not found', $gid));
        }

        /** @var string $name */
        $name = $info['name'];
        /** @var string $passwd */
        $passwd = $info['passwd'];
        /** @var int $gidValue */
        $gidValue = $info['gid'];
        /** @var array<string> $members */
        $members = $info['members'];

        return new self($name, $passwd, $gidValue, $members);
    }

    /**
     * Get current group
     * @throws \RuntimeException
     */
    public static function current(): self
    {
        return self::fromGid(posix_getgid());
    }

    /**
     * Get effective group
     * @throws \RuntimeException
     */
    public static function effective(): self
    {
        return self::fromGid(posix_getegid());
    }

    public function getName(): Str
    {
        return new Str($this->name);
    }

    /**
     * @return Arr<string>
     */
    public function getMembers(): Arr
    {
        return new Arr($this->members);
    }
}
