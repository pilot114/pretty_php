<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

use PrettyPhp\Exception\PrettyPhpException;

/**
 * Base exception for CURL errors
 */
class CurlException extends PrettyPhpException
{
    public function __construct(
        string $message,
        public readonly int $curlCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $curlCode, $previous);
    }

    public static function fromHandle(\CurlHandle $handle): self
    {
        $code = curl_errno($handle);
        $message = curl_error($handle);

        return new self($message !== '' ? $message : 'Unknown CURL error', $code);
    }
}
