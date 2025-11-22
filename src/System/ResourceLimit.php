<?php

declare(strict_types=1);

namespace PrettyPhp\System;

/**
 * Value object representing resource limits
 */
readonly class ResourceLimit
{
    public const CORE = 4;

          // RLIMIT_CORE
    public const DATA = 2;

          // RLIMIT_DATA
    public const STACK = 3;

         // RLIMIT_STACK
    public const AS = 9;

            // RLIMIT_AS (address space)
    public const RSS = 5;

           // RLIMIT_RSS
    public const NPROC = 7;

         // RLIMIT_NPROC
    public const NOFILE = 8;

        // RLIMIT_NOFILE
    public const MEMLOCK = 6;

       // RLIMIT_MEMLOCK
    public const CPU = 0;

           // RLIMIT_CPU
    public const FSIZE = 1;     // RLIMIT_FSIZE

    public const UNLIMITED = 'unlimited';

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

        // posix_getrlimit can return different formats:
        // - numeric indices: [0 => soft, 1 => hard]
        // - named keys: ['soft limit' => soft, 'hard limit' => hard] or ['soft' => soft, 'hard' => hard]
        /** @var int|string|null $soft */
        $soft = $limit['soft limit'] ?? $limit['soft'] ?? $limit[0] ?? null;
        /** @var int|string|null $hard */
        $hard = $limit['hard limit'] ?? $limit['hard'] ?? $limit[1] ?? null;

        if ($soft === null || $hard === null) {
            throw new \RuntimeException('Invalid resource limit data for resource ' . $resource);
        }

        return new self($soft, $hard);
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
