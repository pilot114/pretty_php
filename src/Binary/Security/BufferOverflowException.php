<?php

declare(strict_types=1);

namespace PrettyPhp\Binary\Security;

/**
 * BufferOverflowException - Thrown when buffer size limits are exceeded
 */
class BufferOverflowException extends SecurityException
{
    public function __construct(int $requestedSize, int $maxSize)
    {
        parent::__construct(
            sprintf(
                'Buffer overflow protection: Requested size (%d bytes) exceeds maximum allowed size (%d bytes)',
                $requestedSize,
                $maxSize
            )
        );
    }
}
