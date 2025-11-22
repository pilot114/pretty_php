<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * Main Curl factory and convenience methods
 *
 * Provides a simple, fluent interface for making HTTP requests
 * and creating CURL handles with common configurations.
 */
final class Curl
{
    /**
     * Create a new CURL handle
     *
     * @param string|null $url Optional URL to initialize with
     * @return CurlHandle
     */
    public static function init(?string $url = null): CurlHandle
    {
        return new CurlHandle($url);
    }

    /**
     * Create a new CURL Multi handle for parallel requests
     *
     * @return CurlMultiHandle
     */
    public static function multi(): CurlMultiHandle
    {
        return new CurlMultiHandle();
    }

    /**
     * Create a new CURL Share handle for sharing data between handles
     *
     * @return CurlShareHandle
     */
    public static function share(): CurlShareHandle
    {
        return new CurlShareHandle();
    }

    /**
     * Perform a simple GET request
     *
     * @param string $url The URL to request
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    public static function get(
        string $url,
        array $headers = [],
        array $options = []
    ): string {
        return self::request(HttpMethod::GET, $url, null, $headers, $options);
    }

    /**
     * Perform a POST request
     *
     * @param string $url The URL to request
     * @param mixed $data Data to send (array will be encoded as form data)
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    public static function post(
        string $url,
        mixed $data = null,
        array $headers = [],
        array $options = []
    ): string {
        return self::request(HttpMethod::POST, $url, $data, $headers, $options);
    }

    /**
     * Perform a PUT request
     *
     * @param string $url The URL to request
     * @param mixed $data Data to send
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    public static function put(
        string $url,
        mixed $data = null,
        array $headers = [],
        array $options = []
    ): string {
        return self::request(HttpMethod::PUT, $url, $data, $headers, $options);
    }

    /**
     * Perform a DELETE request
     *
     * @param string $url The URL to request
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    public static function delete(
        string $url,
        array $headers = [],
        array $options = []
    ): string {
        return self::request(HttpMethod::DELETE, $url, null, $headers, $options);
    }

    /**
     * Perform a PATCH request
     *
     * @param string $url The URL to request
     * @param mixed $data Data to send
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    public static function patch(
        string $url,
        mixed $data = null,
        array $headers = [],
        array $options = []
    ): string {
        return self::request(HttpMethod::PATCH, $url, $data, $headers, $options);
    }

    /**
     * Perform a HEAD request
     *
     * @param string $url The URL to request
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response headers
     * @throws CurlException If request fails
     */
    public static function head(
        string $url,
        array $headers = [],
        array $options = []
    ): string {
        $options[CURLOPT_NOBODY] = true;
        return self::request(HttpMethod::HEAD, $url, null, $headers, $options);
    }

    /**
     * Create a CURLFile for file uploads
     *
     * @param string $filename Path to the file
     * @param string|null $mimeType MIME type of the file
     * @param string|null $postFilename Filename to use in the upload
     */
    public static function file(
        string $filename,
        ?string $mimeType = null,
        ?string $postFilename = null
    ): \CURLFile {
        return CurlHandle::createFile($filename, $mimeType, $postFilename);
    }

    /**
     * Get CURL version information
     *
     * @return array{version_number: int, age: int, features: int, ssl_version_number: int, version: string, host: string, ssl_version: string, libz_version: string, protocols: array<int, string>}
     */
    public static function version(): array
    {
        /** @var array{version_number: int, age: int, features: int, ssl_version_number: int, version: string, host: string, ssl_version: string, libz_version: string, protocols: array<int, string>} */
        return CurlHandle::version();
    }

    /**
     * Internal method to perform HTTP requests
     *
     * @param HttpMethod $method HTTP method
     * @param string $url URL to request
     * @param mixed $data Optional data to send
     * @param array<string, string> $headers Optional headers
     * @param array<int, mixed> $options Additional CURL options
     * @return string Response body
     * @throws CurlException If request fails
     */
    private static function request(
        HttpMethod $method,
        string $url,
        mixed $data = null,
        array $headers = [],
        array $options = []
    ): string {
        $curl = new CurlHandle($url);

        // Set method
        $options[CURLOPT_CUSTOMREQUEST] = $method->value;

        // Handle data
        if ($data !== null) {
            if (is_array($data)) {
                $options[CURLOPT_POSTFIELDS] = http_build_query($data);
            } elseif (is_string($data)) {
                $options[CURLOPT_POSTFIELDS] = $data;
            } else {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        // Format headers
        if ($headers !== []) {
            $formattedHeaders = [];
            foreach ($headers as $key => $value) {
                $formattedHeaders[] = sprintf('%s: %s', $key, $value);
            }

            $options[CURLOPT_HTTPHEADER] = $formattedHeaders;
        }

        // Always return response as string
        $options[CURLOPT_RETURNTRANSFER] = true;

        // Set all options
        $curl->setOptions($options);

        // Execute and return
        $result = $curl->execute();

        return is_string($result) ? $result : '';
    }
}
