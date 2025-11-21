<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

use RuntimeException;

/**
 * Exception for CURL Share errors
 */
class CurlShareException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $curlShareCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $curlShareCode, $previous);
    }

    public static function fromHandle(\CurlShareHandle $handle): self
    {
        $code = curl_share_errno($handle);
        $message = curl_share_strerror($code) ?? 'Unknown CURL Share error';

        return new self($message, $code);
    }
}
