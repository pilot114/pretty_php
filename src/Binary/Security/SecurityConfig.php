<?php

declare(strict_types=1);

namespace PrettyPhp\Binary\Security;

/**
 * SecurityConfig - Configuration for security features
 *
 * Holds configuration for various security features including
 * buffer overflow protection and rate limiting.
 */
class SecurityConfig
{
    /**
     * Maximum buffer size for unpacking operations (in bytes)
     * Default: 10MB
     */
    public const DEFAULT_MAX_BUFFER_SIZE = 10 * 1024 * 1024;

    /**
     * Maximum allowed nesting depth for binary structures
     * Prevents stack overflow attacks
     */
    public const DEFAULT_MAX_NESTING_DEPTH = 100;

    /**
     * Rate limiting defaults
     */
    public const DEFAULT_RATE_LIMIT_REQUESTS = 1000;
    public const DEFAULT_RATE_LIMIT_WINDOW = 60; // seconds

    private static int $maxBufferSize = self::DEFAULT_MAX_BUFFER_SIZE;
    private static int $maxNestingDepth = self::DEFAULT_MAX_NESTING_DEPTH;
    private static bool $strictMode = false;

    /**
     * Set maximum buffer size for unpacking operations
     */
    public static function setMaxBufferSize(int $size): void
    {
        if ($size <= 0) {
            throw new SecurityException('Max buffer size must be positive');
        }
        self::$maxBufferSize = $size;
    }

    /**
     * Get maximum buffer size
     */
    public static function getMaxBufferSize(): int
    {
        return self::$maxBufferSize;
    }

    /**
     * Set maximum nesting depth
     */
    public static function setMaxNestingDepth(int $depth): void
    {
        if ($depth <= 0) {
            throw new SecurityException('Max nesting depth must be positive');
        }
        self::$maxNestingDepth = $depth;
    }

    /**
     * Get maximum nesting depth
     */
    public static function getMaxNestingDepth(): int
    {
        return self::$maxNestingDepth;
    }

    /**
     * Enable strict security mode
     *
     * In strict mode:
     * - All size validations are enforced
     * - Rate limiting is mandatory
     * - Additional security checks are performed
     */
    public static function enableStrictMode(): void
    {
        self::$strictMode = true;
    }

    /**
     * Disable strict security mode
     */
    public static function disableStrictMode(): void
    {
        self::$strictMode = false;
    }

    /**
     * Check if strict mode is enabled
     */
    public static function isStrictMode(): bool
    {
        return self::$strictMode;
    }

    /**
     * Reset to default configuration
     */
    public static function reset(): void
    {
        self::$maxBufferSize = self::DEFAULT_MAX_BUFFER_SIZE;
        self::$maxNestingDepth = self::DEFAULT_MAX_NESTING_DEPTH;
        self::$strictMode = false;
    }
}
