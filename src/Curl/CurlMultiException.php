<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

use RuntimeException;

/**
 * Exception for CURL Multi errors
 */
class CurlMultiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $curlMultiCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $curlMultiCode, $previous);
    }

    public static function fromHandle(\CurlMultiHandle $handle): self
    {
        $code = curl_multi_errno($handle);
        $message = curl_multi_strerror($code) ?? 'Unknown CURL Multi error';

        return new self($message, $code);
    }
}
