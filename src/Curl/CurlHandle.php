<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * Modern object-oriented wrapper for CURL operations
 *
 * This class wraps the following CURL functions:
 * - curl_init, curl_close, curl_reset
 * - curl_setopt, curl_setopt_array
 * - curl_exec
 * - curl_getinfo
 * - curl_errno, curl_error, curl_strerror
 * - curl_escape, curl_unescape
 * - curl_copy_handle
 * - curl_pause
 * - curl_upkeep
 * - curl_file_create
 */
final class CurlHandle
{
    private \CurlHandle $handle;
    private bool $closed = false;

    /**
     * Create a new CURL handle
     *
     * @param string|null $url Optional URL to initialize with
     */
    public function __construct(?string $url = null)
    {
        /** @phpstan-ignore assign.propertyType */
        $this->handle = curl_init($url);
    }

    /**
     * Get the underlying CurlHandle resource
     *
     * @throws CurlException If handle is closed
     */
    public function getHandle(): \CurlHandle
    {
        $this->ensureHandleExists();
        return $this->handle;
    }

    /**
     * Set a single CURL option
     *
     * @param int $option The CURLOPT_XXX constant
     * @param mixed $value The value for the option
     * @return self For method chaining
     * @throws CurlException If setting the option fails
     */
    public function setOption(int $option, mixed $value): self
    {
        $this->ensureHandleExists();

        if (!curl_setopt($this->handle, $option, $value)) {
            throw new CurlException("Failed to set CURL option {$option}");
        }

        return $this;
    }

    /**
     * Set multiple CURL options at once
     *
     * @param array<int, mixed> $options Array of CURLOPT_XXX => value pairs
     * @return self For method chaining
     * @throws CurlException If setting options fails
     */
    public function setOptions(array $options): self
    {
        $this->ensureHandleExists();

        if (!curl_setopt_array($this->handle, $options)) {
            throw new CurlException('Failed to set CURL options');
        }

        return $this;
    }

    /**
     * Execute the CURL request
     *
     * @return string|bool The response body or true on success (depends on CURLOPT_RETURNTRANSFER)
     * @throws CurlException If the request fails
     */
    public function execute(): string|bool
    {
        $this->ensureHandleExists();

        $result = curl_exec($this->handle);

        if ($result === false) {
            throw CurlException::fromHandle($this->handle);
        }

        return $result;
    }

    /**
     * Get information about the last transfer
     *
     * @param int|null $option Specific CURLINFO_XXX constant or null for all info
     * @return mixed Information about the transfer
     * @throws CurlException If handle is closed
     */
    public function getInfo(?int $option = null): mixed
    {
        $this->ensureHandleExists();

        return $option === null
            ? curl_getinfo($this->handle)
            : curl_getinfo($this->handle, $option);
    }

    /**
     * Get the last error number
     *
     * @throws CurlException If handle is closed
     */
    public function getErrorNumber(): int
    {
        $this->ensureHandleExists();
        return curl_errno($this->handle);
    }

    /**
     * Get the last error message
     *
     * @throws CurlException If handle is closed
     */
    public function getErrorMessage(): string
    {
        $this->ensureHandleExists();
        return curl_error($this->handle);
    }

    /**
     * Get error string for a given error code
     */
    public static function getErrorString(int $errorCode): ?string
    {
        return curl_strerror($errorCode);
    }

    /**
     * URL encode a string
     *
     * @throws CurlException If handle is closed or escaping fails
     */
    public function escape(string $string): string
    {
        $this->ensureHandleExists();
        $result = curl_escape($this->handle, $string);

        if ($result === false) {
            throw new CurlException('Failed to escape string');
        }

        return $result;
    }

    /**
     * URL decode a string
     *
     * @throws CurlException If handle is closed or unescaping fails
     */
    public function unescape(string $string): string
    {
        $this->ensureHandleExists();
        $result = curl_unescape($this->handle, $string);

        if ($result === false) {
            throw new CurlException('Failed to unescape string');
        }

        return $result;
    }

    /**
     * Copy this CURL handle with all its options
     *
     * @return self A new instance with copied handle
     * @throws CurlException If copying fails
     */
    public function copy(): self
    {
        $this->ensureHandleExists();

        $newHandle = curl_copy_handle($this->handle);

        if ($newHandle === false) {
            throw new CurlException('Failed to copy CURL handle');
        }

        $new = new self();
        $new->handle = $newHandle;

        return $new;
    }

    /**
     * Reset all options to default values
     *
     * @return self For method chaining
     * @throws CurlException If handle is closed
     */
    public function reset(): self
    {
        $this->ensureHandleExists();
        curl_reset($this->handle);

        return $this;
    }

    /**
     * Pause or unpause a connection
     *
     * @param int $bitmask CURLPAUSE_* constants
     * @return self For method chaining
     * @throws CurlException If pause fails
     */
    public function pause(int $bitmask): self
    {
        $this->ensureHandleExists();

        $result = curl_pause($this->handle, $bitmask);

        if ($result !== 0) {
            throw new CurlException("Failed to pause/unpause connection: error code {$result}");
        }

        return $this;
    }

    /**
     * Perform connection upkeep checks
     *
     * @return self For method chaining
     * @throws CurlException If upkeep fails
     */
    public function upkeep(): self
    {
        $this->ensureHandleExists();

        if (!curl_upkeep($this->handle)) {
            throw new CurlException('Failed to perform connection upkeep');
        }

        return $this;
    }

    /**
     * Get CURL version information
     *
     * @return array<string, mixed> Version information
     */
    public static function version(): array
    {
        /** @var array<string, mixed> */
        $version = curl_version();
        return is_array($version) ? $version : [];
    }

    /**
     * Create a CURLFile object for file uploads
     *
     * @param string $filename Path to the file
     * @param string|null $mimeType MIME type of the file
     * @param string|null $postFilename Filename to use in the upload
     * @return \CURLFile File object for uploading
     */
    public static function createFile(
        string $filename,
        ?string $mimeType = null,
        ?string $postFilename = null
    ): \CURLFile {
        return curl_file_create($filename, $mimeType, $postFilename);
    }

    /**
     * Close the CURL handle and free resources
     */
    public function close(): void
    {
        if (!$this->closed) {
            curl_close($this->handle);
            $this->closed = true;
        }
    }

    /**
     * Automatically close handle on destruction
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Ensure the handle exists before operations
     *
     * @throws CurlException If handle is closed
     */
    private function ensureHandleExists(): void
    {
        if ($this->closed) {
            throw new CurlException('CURL handle is closed');
        }
    }
}
