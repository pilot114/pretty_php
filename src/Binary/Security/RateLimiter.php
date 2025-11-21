<?php

declare(strict_types=1);

namespace PrettyPhp\Binary\Security;

/**
 * RateLimiter - Token bucket rate limiter for network operations
 *
 * Implements a token bucket algorithm to limit the rate of operations.
 * This prevents abuse and DoS attacks on network operations.
 */
class RateLimiter
{
    private int $tokens;

    private float $lastRefill;

    private bool $enabled = true;

    /**
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(
        private readonly int $maxRequests,
        private readonly int $windowSeconds
    ) {
        if ($maxRequests <= 0) {
            throw new SecurityException('Max requests must be positive');
        }

        if ($windowSeconds <= 0) {
            throw new SecurityException('Window seconds must be positive');
        }

        $this->tokens = $maxRequests;
        $this->lastRefill = microtime(true);
    }

    /**
     * Create a rate limiter with default settings
     */
    public static function default(): self
    {
        return new self(
            SecurityConfig::DEFAULT_RATE_LIMIT_REQUESTS,
            SecurityConfig::DEFAULT_RATE_LIMIT_WINDOW
        );
    }

    /**
     * Create a strict rate limiter (lower limits)
     */
    public static function strict(): self
    {
        return new self(100, 60); // 100 requests per minute
    }

    /**
     * Create a permissive rate limiter (higher limits)
     */
    public static function permissive(): self
    {
        return new self(10000, 60); // 10,000 requests per minute
    }

    /**
     * Check if an operation is allowed
     *
     * @param string $operation Operation identifier for error messages
     * @throws RateLimitException if rate limit is exceeded
     */
    public function checkLimit(string $operation = 'operation'): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->refillTokens();

        if ($this->tokens < 1) {
            throw new RateLimitException(
                $operation,
                $this->maxRequests,
                $this->windowSeconds
            );
        }

        $this->tokens--;
    }

    /**
     * Try to perform an operation, returns false if rate limit exceeded
     */
    public function tryOperation(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $this->refillTokens();

        if ($this->tokens < 1) {
            return false;
        }

        $this->tokens--;
        return true;
    }

    /**
     * Get remaining tokens
     */
    public function getRemainingTokens(): int
    {
        $this->refillTokens();
        return max(0, $this->tokens);
    }

    /**
     * Get time until next token is available (in seconds)
     */
    public function getTimeUntilNextToken(): float
    {
        $this->refillTokens();

        if ($this->tokens >= 1) {
            return 0.0;
        }

        $tokensPerSecond = $this->maxRequests / $this->windowSeconds;
        return (1 - $this->tokens) / $tokensPerSecond;
    }

    /**
     * Enable rate limiting
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable rate limiting (useful for testing)
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if rate limiting is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Reset the rate limiter (refill all tokens)
     */
    public function reset(): void
    {
        $this->tokens = $this->maxRequests;
        $this->lastRefill = microtime(true);
    }

    /**
     * Refill tokens based on elapsed time
     */
    private function refillTokens(): void
    {
        $now = microtime(true);
        $elapsed = $now - $this->lastRefill;

        // Calculate tokens to add based on elapsed time
        $tokensPerSecond = $this->maxRequests / $this->windowSeconds;
        $tokensToAdd = (int) floor($elapsed * $tokensPerSecond);

        if ($tokensToAdd > 0) {
            $this->tokens = min($this->maxRequests, $this->tokens + $tokensToAdd);
            $this->lastRefill = $now;
        }
    }
}
