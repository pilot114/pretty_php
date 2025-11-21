<?php

declare(strict_types=1);

namespace PrettyPhp\Binary\Security;

/**
 * RateLimitException - Thrown when rate limit is exceeded
 */
class RateLimitException extends SecurityException
{
    public function __construct(string $operation, int $limit, int $window)
    {
        parent::__construct(
            sprintf(
                'Rate limit exceeded for %s: Maximum %d requests per %d seconds',
                $operation,
                $limit,
                $window
            )
        );
    }
}
