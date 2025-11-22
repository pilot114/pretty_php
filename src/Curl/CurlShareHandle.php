<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * Modern object-oriented wrapper for CURL Share operations
 *
 * This class wraps the following CURL Share functions:
 * - curl_share_init, curl_share_close
 * - curl_share_setopt
 * - curl_share_errno, curl_share_strerror
 *
 * CURL Share allows sharing of data between CURL handles, such as:
 * - DNS cache (CURL_LOCK_DATA_DNS)
 * - SSL session IDs (CURL_LOCK_DATA_SSL_SESSION)
 * - Connection cache (CURL_LOCK_DATA_CONNECT)
 */
final class CurlShareHandle
{
    private readonly \CurlShareHandle $handle;

    private bool $closed = false;

    /**
     * Create a new CURL Share handle
     */
    public function __construct()
    {
        $this->handle = curl_share_init();
    }

    /**
     * Get the underlying CurlShareHandle resource
     *
     * @throws CurlShareException If handle is closed
     */
    public function getHandle(): \CurlShareHandle
    {
        $this->ensureHandleExists();
        return $this->handle;
    }

    /**
     * Set an option for the share handle
     *
     * @param int $option The CURLSHOPT_XXX constant
     * @param mixed $value The value for the option
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function setOption(int $option, mixed $value): self
    {
        $this->ensureHandleExists();

        if (!curl_share_setopt($this->handle, $option, $value)) {
            $code = $this->getErrorNumber();
            throw new CurlShareException(
                "Failed to set share option: " . curl_share_strerror($code),
                $code
            );
        }

        return $this;
    }

    /**
     * Share DNS cache between handles
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function shareDns(): self
    {
        return $this->setOption(CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
    }

    /**
     * Share SSL session IDs between handles
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function shareSslSession(): self
    {
        return $this->setOption(CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
    }

    /**
     * Share connection cache between handles
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function shareConnect(): self
    {
        return $this->setOption(CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);
    }

    /**
     * Unshare DNS cache
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function unshareDns(): self
    {
        return $this->setOption(CURLSHOPT_UNSHARE, CURL_LOCK_DATA_DNS);
    }

    /**
     * Unshare SSL session IDs
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function unshareSslSession(): self
    {
        return $this->setOption(CURLSHOPT_UNSHARE, CURL_LOCK_DATA_SSL_SESSION);
    }

    /**
     * Unshare connection cache
     *
     * @return self For method chaining
     * @throws CurlShareException If setting the option fails
     */
    public function unshareConnect(): self
    {
        return $this->setOption(CURLSHOPT_UNSHARE, CURL_LOCK_DATA_CONNECT);
    }

    /**
     * Get the last error number
     *
     * @throws CurlShareException If handle is closed
     */
    public function getErrorNumber(): int
    {
        $this->ensureHandleExists();
        return curl_share_errno($this->handle);
    }

    /**
     * Get error string for a given error code
     */
    public static function getErrorString(int $errorCode): ?string
    {
        return curl_share_strerror($errorCode);
    }

    /**
     * Close the share handle and free resources
     */
    public function close(): void
    {
        if (!$this->closed) {
            curl_share_close($this->handle);
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
     * @throws CurlShareException If handle is closed
     */
    private function ensureHandleExists(): void
    {
        if ($this->closed) {
            throw new CurlShareException('CURL Share handle is closed');
        }
    }
}
