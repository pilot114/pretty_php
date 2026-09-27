<?php

declare(strict_types=1);

namespace PrettyPhp\System;

/**
 * Value object representing resource limits
 */
readonly class ResourceLimit
{
    public const int CORE = 4;

          // RLIMIT_CORE
    public const int DATA = 2;

          // RLIMIT_DATA
    public const int STACK = 3;

         // RLIMIT_STACK
    public const int AS = 9;

            // RLIMIT_AS (address space)
    public const int RSS = 5;

           // RLIMIT_RSS
    public const int NPROC = 7;

         // RLIMIT_NPROC
    public const int NOFILE = 8;

        // RLIMIT_NOFILE
    public const int MEMLOCK = 6;

       // RLIMIT_MEMLOCK
    public const int CPU = 0;

           // RLIMIT_CPU
    public const int FSIZE = 1;     // RLIMIT_FSIZE

    public const string UNLIMITED = 'unlimited';

    public function __construct(
        public int|string $soft,
        public int|string $hard
    ) {
    }

    /**
     * Get resource limit
     * @throws \RuntimeException
     */
    public static function get(int $resource): self
    {
        $limit = posix_getrlimit($resource);
        if ($limit === false) {
            throw new \RuntimeException('Failed to get resource limit for resource ' . $resource);
        }

        // Since PHP 8.3 a single resource is returned as [0 => soft, 1 => hard]
        return new self($limit[0], $limit[1]);
    }

    /**
     * Set resource limit
     * @throws \RuntimeException
     */
    public static function set(int $resource, int $softLimit, int $hardLimit): bool
    {
        $result = posix_setrlimit($resource, $softLimit, $hardLimit);
        if (!$result) {
            throw new \RuntimeException('Failed to set resource limit for resource ' . $resource);
        }

        return true;
    }

    public function isSoftUnlimited(): bool
    {
        return $this->soft === self::UNLIMITED;
    }

    public function isHardUnlimited(): bool
    {
        return $this->hard === self::UNLIMITED;
    }

    public function getSoftLimit(): int|string
    {
        return $this->soft;
    }

    public function getHardLimit(): int|string
    {
        return $this->hard;
    }
}
