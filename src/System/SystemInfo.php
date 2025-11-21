<?php

declare(strict_types=1);

namespace PrettyPhp\System;

use PrettyPhp\Base\Str;

/**
 * Value object representing system information from uname
 */
readonly class SystemInfo
{
    public function __construct(
        public string $sysname,
        public string $nodename,
        public string $release,
        public string $version,
        public string $machine,
        public ?string $domainname = null
    ) {
    }

    /**
     * Get system information
     * @throws \RuntimeException
     */
    public static function get(): self
    {
        $info = posix_uname();
        if ($info === false) {
            throw new \RuntimeException("Failed to get system information");
        }

        return new self(
            $info['sysname'],
            $info['nodename'],
            $info['release'],
            $info['version'],
            $info['machine'],
            $info['domainname'] ?? null
        );
    }

    public function getSysname(): Str
    {
        return new Str($this->sysname);
    }

    public function getNodename(): Str
    {
        return new Str($this->nodename);
    }

    public function getRelease(): Str
    {
        return new Str($this->release);
    }

    public function getVersion(): Str
    {
        return new Str($this->version);
    }

    public function getMachine(): Str
    {
        return new Str($this->machine);
    }

    public function getDomainname(): ?Str
    {
        return $this->domainname !== null ? new Str($this->domainname) : null;
    }

    public function isLinux(): bool
    {
        return strtolower($this->sysname) === 'linux';
    }

    public function isBSD(): bool
    {
        return str_contains(strtolower($this->sysname), 'bsd');
    }

    public function isMacOS(): bool
    {
        return strtolower($this->sysname) === 'darwin';
    }
}
