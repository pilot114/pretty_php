<?php

namespace PrettyPhp\Binary;

/**
 * HTTP Packet
 * Basic HTTP packet for requests and responses
 *
 * Note: HTTP is a text-based protocol, so this class doesn't use the Binary attribute system.
 * It provides methods to build and parse HTTP packets.
 */
class HTTPPacket
{
    public const METHOD_GET = 'GET';

    public const METHOD_POST = 'POST';

    public const METHOD_PUT = 'PUT';

    public const METHOD_DELETE = 'DELETE';

    public const METHOD_HEAD = 'HEAD';

    public const METHOD_OPTIONS = 'OPTIONS';

    public const METHOD_PATCH = 'PATCH';

    public const HTTP_VERSION_10 = 'HTTP/1.0';

    public const HTTP_VERSION_11 = 'HTTP/1.1';

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method = self::METHOD_GET,
        public string $uri = '/',
        public string $version = self::HTTP_VERSION_11,
        public int $statusCode = 200,
        public string $reasonPhrase = 'OK',
        public array $headers = [],
        public string $body = '',
        public bool $isRequest = true,
    ) {
    }

    /**
     * Create an HTTP request packet
     *
     * @param array<string, string> $headers
     */
    public static function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        string $body = '',
        string $version = self::HTTP_VERSION_11
    ): self {
        return new self(
            method: $method,
            uri: $uri,
            version: $version,
            headers: $headers,
            body: $body,
            isRequest: true
        );
    }

    /**
     * Create an HTTP response packet
     *
     * @param array<string, string> $headers
     */
    public static function createResponse(
        int $statusCode,
        string $reasonPhrase = '',
        array $headers = [],
        string $body = '',
        string $version = self::HTTP_VERSION_11
    ): self {
        if ($reasonPhrase === '') {
            $reasonPhrase = self::getReasonPhrase($statusCode);
        }

        return new self(
            version: $version,
            statusCode: $statusCode,
            reasonPhrase: $reasonPhrase,
            headers: $headers,
            body: $body,
            isRequest: false
        );
    }

    /**
     * Convert the packet to raw HTTP format
     */
    public function toRaw(): string
    {
        $lines = [];

        if ($this->isRequest) {
            // Request line: METHOD URI VERSION
            $lines[] = sprintf('%s %s %s', $this->method, $this->uri, $this->version);
        } else {
            // Status line: VERSION STATUS_CODE REASON_PHRASE
            $lines[] = sprintf('%s %d %s', $this->version, $this->statusCode, $this->reasonPhrase);
        }

        // Headers
        foreach ($this->headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }

        // Empty line separating headers from body
        $lines[] = '';

        // Body
        if ($this->body !== '') {
            $lines[] = $this->body;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Parse raw HTTP data into an HTTPPacket
     */
    public static function fromRaw(string $raw): self
    {
        $lines = explode("\r\n", $raw);
        if ($lines === []) {
            throw new \Exception('Empty HTTP packet');
        }

        $firstLine = array_shift($lines);
        $parts = explode(' ', $firstLine, 3);

        // Determine if this is a request or response
        $isRequest = !str_starts_with($parts[0], 'HTTP/');

        $packet = new self();

        if ($isRequest) {
            // Request: METHOD URI VERSION
            $packet->isRequest = true;
            $packet->method = $parts[0] ?? self::METHOD_GET;
            $packet->uri = $parts[1] ?? '/';
            $packet->version = $parts[2] ?? self::HTTP_VERSION_11;
        } else {
            // Response: VERSION STATUS_CODE REASON_PHRASE
            $packet->isRequest = false;
            $packet->version = $parts[0] ?? self::HTTP_VERSION_11;
            $packet->statusCode = isset($parts[1]) ? (int) $parts[1] : 200;
            $packet->reasonPhrase = $parts[2] ?? 'OK';
        }

        // Parse headers
        $headers = [];
        while ($lines !== []) {
            $line = array_shift($lines);
            if ($line === '') {
                break; // Empty line separates headers from body
            }

            $headerParts = explode(':', $line, 2);
            if (count($headerParts) === 2) {
                $headers[trim($headerParts[0])] = trim($headerParts[1]);
            }
        }

        $packet->headers = $headers;

        // Remaining lines are the body
        $packet->body = implode("\r\n", $lines);

        return $packet;
    }

    /**
     * Get standard reason phrase for HTTP status code
     */
    private static function getReasonPhrase(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Unknown',
        };
    }
}
