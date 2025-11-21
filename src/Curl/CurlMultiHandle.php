<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * Modern object-oriented wrapper for CURL Multi operations
 *
 * This class wraps the following CURL Multi functions:
 * - curl_multi_init, curl_multi_close
 * - curl_multi_add_handle, curl_multi_remove_handle
 * - curl_multi_exec
 * - curl_multi_select
 * - curl_multi_getcontent
 * - curl_multi_info_read
 * - curl_multi_setopt
 * - curl_multi_errno, curl_multi_strerror
 */
final class CurlMultiHandle
{
    private \CurlMultiHandle $handle;
    private bool $closed = false;

    /** @var array<int, CurlHandle> */
    private array $handles = [];

    /**
     * Create a new CURL Multi handle
     */
    public function __construct()
    {
        $this->handle = curl_multi_init();
    }

    /**
     * Get the underlying CurlMultiHandle resource
     *
     * @throws CurlMultiException If handle is closed
     */
    public function getHandle(): \CurlMultiHandle
    {
        $this->ensureHandleExists();
        return $this->handle;
    }

    /**
     * Add a regular CURL handle to the multi handle
     *
     * @param CurlHandle $curlHandle The handle to add
     * @return self For method chaining
     * @throws CurlMultiException If adding the handle fails
     * @throws CurlException If handle is closed
     */
    public function addHandle(CurlHandle $curlHandle): self
    {
        $this->ensureHandleExists();

        $result = curl_multi_add_handle($this->handle, $curlHandle->getHandle());

        if ($result !== CURLM_OK) {
            throw new CurlMultiException(
                "Failed to add handle: " . curl_multi_strerror($result),
                $result
            );
        }

        $this->handles[spl_object_id($curlHandle)] = $curlHandle;

        return $this;
    }

    /**
     * Remove a CURL handle from the multi handle
     *
     * @param CurlHandle $curlHandle The handle to remove
     * @return self For method chaining
     * @throws CurlMultiException If removing the handle fails
     * @throws CurlException If handle is closed
     */
    public function removeHandle(CurlHandle $curlHandle): self
    {
        $this->ensureHandleExists();

        $result = curl_multi_remove_handle($this->handle, $curlHandle->getHandle());

        if ($result !== CURLM_OK) {
            throw new CurlMultiException(
                "Failed to remove handle: " . curl_multi_strerror($result),
                $result
            );
        }

        unset($this->handles[spl_object_id($curlHandle)]);

        return $this;
    }

    /**
     * Execute all handles
     *
     * @param-out int $stillRunning
     * @param int $stillRunning Reference to get the number of handles still running
     * @return int The status code
     * @throws CurlMultiException If execution fails
     */
    public function execute(int &$stillRunning): int
    {
        $this->ensureHandleExists();

        /** @phpstan-ignore paramOut.type */
        $result = curl_multi_exec($this->handle, $stillRunning);

        if ($result !== CURLM_OK && $result !== CURLM_CALL_MULTI_PERFORM) {
            throw new CurlMultiException(
                "Multi exec failed: " . curl_multi_strerror($result),
                $result
            );
        }

        return $result;
    }

    /**
     * Wait for activity on any connection
     *
     * @param float $timeout Maximum time to wait in seconds
     * @return int The number of descriptors with activity, -1 on failure
     * @throws CurlMultiException If handle is closed
     */
    public function select(float $timeout = 1.0): int
    {
        $this->ensureHandleExists();
        return curl_multi_select($this->handle, $timeout);
    }

    /**
     * Get content of a handle that was added to multi handle
     *
     * @param CurlHandle $curlHandle The handle to get content from
     * @return string|null The content or null if unavailable
     * @throws CurlMultiException If handle is closed
     * @throws CurlException If curl handle is closed
     */
    public function getContent(CurlHandle $curlHandle): ?string
    {
        $this->ensureHandleExists();
        $result = curl_multi_getcontent($curlHandle->getHandle());

        return $result === null ? null : $result;
    }

    /**
     * Get information about the current transfers
     *
     * @param-out int $messagesInQueue
     * @param int $messagesInQueue Reference to get the number of messages still in queue
     * @return array<string, mixed>|false Information array or false if no messages
     * @throws CurlMultiException If handle is closed
     */
    public function infoRead(int &$messagesInQueue = 0): array|false
    {
        $this->ensureHandleExists();
        /** @phpstan-ignore paramOut.type, return.type */
        return curl_multi_info_read($this->handle, $messagesInQueue);
    }

    /**
     * Set an option for the multi handle
     *
     * @param int $option The CURLMOPT_XXX constant
     * @param mixed $value The value for the option
     * @return self For method chaining
     * @throws CurlMultiException If setting the option fails
     */
    public function setOption(int $option, mixed $value): self
    {
        $this->ensureHandleExists();

        if (!curl_multi_setopt($this->handle, $option, $value)) {
            throw new CurlMultiException("Failed to set multi option {$option}");
        }

        return $this;
    }

    /**
     * Get the last error number
     *
     * @throws CurlMultiException If handle is closed
     */
    public function getErrorNumber(): int
    {
        $this->ensureHandleExists();
        return curl_multi_errno($this->handle);
    }

    /**
     * Get error string for a given error code
     */
    public static function getErrorString(int $errorCode): ?string
    {
        return curl_multi_strerror($errorCode);
    }

    /**
     * Execute all handles and wait for completion
     *
     * This is a convenience method that handles the execution loop
     *
     * @return array<int, array{handle: CurlHandle, result: int, content: string|null}> Results for each handle
     * @throws CurlMultiException If execution fails
     * @throws CurlException If handle is closed
     */
    public function executeAll(): array
    {
        $this->ensureHandleExists();

        $stillRunning = 0;

        do {
            $this->execute($stillRunning);

            if ($stillRunning > 0) {
                $this->select();
            }
        } while ($stillRunning > 0);

        // Collect results
        $results = [];
        $messagesInQueue = 0;

        while ($info = $this->infoRead($messagesInQueue)) {
            if ($info['msg'] === CURLMSG_DONE) {
                $curlHandle = $info['handle'];

                foreach ($this->handles as $handle) {
                    if ($handle->getHandle() === $curlHandle) {
                        $results[] = [
                            'handle' => $handle,
                            'result' => is_int($info['result']) ? $info['result'] : 0,
                            'content' => $this->getContent($handle),
                        ];
                        break;
                    }
                }
            }

            if ($messagesInQueue === 0) {
                break;
            }
        }

        return $results;
    }

    /**
     * Close the multi handle and free resources
     *
     * @throws CurlException If curl handle is closed
     */
    public function close(): void
    {
        if (!$this->closed) {
            // Remove all handles first
            foreach ($this->handles as $handle) {
                curl_multi_remove_handle($this->handle, $handle->getHandle());
            }

            curl_multi_close($this->handle);
            $this->closed = true;
            $this->handles = [];
        }
    }

    /**
     * Automatically close handle on destruction
     */
    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Throwable) {
            // Suppress exceptions in destructor
        }
    }

    /**
     * Ensure the handle exists before operations
     *
     * @throws CurlMultiException If handle is closed
     */
    private function ensureHandleExists(): void
    {
        if ($this->closed) {
            throw new CurlMultiException('CURL Multi handle is closed');
        }
    }
}
