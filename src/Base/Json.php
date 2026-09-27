<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

use PrettyPhp\Functional\Result;

// Imported so that the engine compiles them to dedicated opcodes instead of namespaced calls
use function array_key_exists;
use function count;
use function is_array;
use function is_object;
use function is_string;

readonly class Json implements \Stringable
{
    /**
     * Decoded JSON string, filled on first use: path queries and manipulation of the same instance decode
     * it only once. Null for data-backed instances, which have nothing to decode.
     */
    private ?JsonCache $cache;

    /**
     * @param mixed $value The JSON string or data to encode
     * @param bool $isEncoded Whether the value is already a JSON string
     */
    private function __construct(
        private mixed $value,
        private bool $isEncoded = false
    ) {
        $this->cache = $isEncoded ? new JsonCache() : null;
    }

    /**
     * Create from a JSON string
     */
    #[\NoDiscard]
    public static function fromString(string $json): self
    {
        return new self($json, true);
    }

    /**
     * Create from data to be encoded
     */
    #[\NoDiscard]
    public static function fromData(mixed $data): self
    {
        return new self($data, false);
    }

    /**
     * Get the underlying value
     * Returns the JSON string if encoded, or the raw data if not
     */
    public function get(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        if ($this->isEncoded && is_string($this->value)) {
            return $this->value;
        }

        $encoded = json_encode($this->value);
        return $encoded !== false ? $encoded : '{}';
    }

    // ==================== Encoding/Decoding ====================

    /**
     * Encode data to JSON string
     *
     * @param int<1, max> $depth
     * @return Result<Str, string> Ok with Str on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function encode(int $flags = 0, int $depth = 512): Result
    {
        if ($this->isEncoded && is_string($this->value)) {
            return Result::ok(new Str($this->value));
        }

        $json = json_encode($this->value, $flags, $depth);

        if ($json === false) {
            return Result::err(json_last_error_msg());
        }

        return Result::ok(new Str($json));
    }

    /**
     * Decode JSON string to array
     *
     * @param int<1, max> $depth
     * @return Result<Arr<mixed>, string> Ok with Arr on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function decode(bool $associative = true, int $depth = 512, int $flags = 0): Result
    {
        if (!$this->isEncoded || !is_string($this->value)) {
            return Result::err('Cannot decode: value is not a JSON string');
        }

        $decoded = json_decode($this->value, $associative, $depth, $flags);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Result::err(json_last_error_msg());
        }

        return Result::ok(new Arr($associative ? (array) $decoded : [$decoded]));
    }

    /**
     * Decode JSON string to object
     *
     * @param int<1, max> $depth
     * @return Result<object, string> Ok with object on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function decodeObject(int $depth = 512, int $flags = 0): Result
    {
        if (!$this->isEncoded || !is_string($this->value)) {
            return Result::err('Cannot decode: value is not a JSON string');
        }

        $decoded = json_decode($this->value, false, $depth, $flags);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Result::err(json_last_error_msg());
        }

        /** @var object $decoded */
        return Result::ok($decoded);
    }

    // ==================== Validation ====================

    /**
     * Check if the JSON is valid
     */
    public function isValid(): bool
    {
        if (!$this->isEncoded) {
            // Try to encode to check if data is encodable
            json_encode($this->value);
            return json_last_error() === JSON_ERROR_NONE;
        }

        try {
            $this->data();
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    /**
     * Validate and return Result
     *
     * @return Result<self, string> Ok with self on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function validate(): Result
    {
        if (!$this->isEncoded) {
            json_encode($this->value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return Result::err(json_last_error_msg());
            }
        } else {
            try {
                $this->data();
            } catch (\JsonException $jsonException) {
                return Result::err($jsonException->getMessage());
            }
        }

        /** @phpstan-ignore return.type (invariant template: $this vs self) */
        return Result::ok($this);
    }

    // ==================== Formatting ====================

    /**
     * Pretty print JSON with indentation
     */
    #[\NoDiscard]
    public function pretty(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): self
    {
        try {
            $pretty = json_encode($this->data(), $flags);
        } catch (\JsonException) {
            return $this;
        }

        if ($pretty === false) {
            return $this;
        }

        return new self($pretty, true);
    }

    /**
     * Minify JSON by removing whitespace
     */
    #[\NoDiscard]
    public function minify(): self
    {
        try {
            $minified = json_encode($this->data());
        } catch (\JsonException) {
            return $this;
        }

        if ($minified === false) {
            return $this;
        }

        return new self($minified, true);
    }

    // ==================== JSON Path Queries ====================

    /**
     * Query JSON using a simple path (e.g., "user.name", "users.0.email")
     *
     * @return Result<mixed, string> Ok with value on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function path(string $path): Result
    {
        try {
            $current = $this->data();
        } catch (\JsonException $jsonException) {
            return Result::err($jsonException->getMessage());
        }

        if (!$this->find($current, $path)) {
            /** @phpstan-ignore return.type (invariant template: never vs mixed) */
            return Result::err('Path not found: ' . $path);
        }

        return Result::ok($current);
    }

    /**
     * Check if a path exists in the JSON
     */
    public function hasPath(string $path): bool
    {
        try {
            $current = $this->data();
        } catch (\JsonException) {
            return false;
        }

        return $this->find($current, $path);
    }

    /**
     * Walk a dot-separated path, replacing $current with the value found at it
     */
    private function find(mixed &$current, string $path): bool
    {
        foreach (explode('.', $path) as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_object($current) && property_exists($current, $key)) {
                $current = ((array) $current)[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    // ==================== Manipulation ====================

    /**
     * Get the value as PHP data, decoding it (as associative arrays) if it is a JSON string
     *
     * @throws \JsonException If the JSON string is invalid
     */
    private function data(): mixed
    {
        $cache = $this->cache;
        if (!$cache instanceof JsonCache || !is_string($this->value)) {
            return $this->value;
        }

        if (!$cache->isDecoded) {
            $cache->decoded = json_decode($this->value, true, 512, JSON_THROW_ON_ERROR);
            $cache->isDecoded = true;
        }

        return $cache->decoded;
    }

    /**
     * Create a typed error Result for this class.
     *
     * @return Result<self, string>
     */
    private function error(string $message): Result
    {
        return Result::err($message);
    }

    /**
     * Merge with another JSON object
     *
     * @return Result<self, string> Ok with merged JSON on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function merge(self $other): Result
    {
        try {
            $thisDecoded = $this->data();
        } catch (\JsonException $jsonException) {
            return $this->error('Failed to decode this JSON: ' . $jsonException->getMessage());
        }

        try {
            $otherDecoded = $other->data();
        } catch (\JsonException $jsonException) {
            return $this->error('Failed to decode other JSON: ' . $jsonException->getMessage());
        }

        // Merge arrays recursively
        if (!is_array($thisDecoded) || !is_array($otherDecoded)) {
            return $this->error('Both values must be arrays/objects to merge');
        }

        $merged = array_merge_recursive($thisDecoded, $otherDecoded);

        return Result::ok(new self($merged, false));
    }

    /**
     * Set a value at a path
     *
     * @return Result<self, string> Ok with new JSON on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function set(string $path, mixed $value): Result
    {
        try {
            $decoded = $this->data();
        } catch (\JsonException $jsonException) {
            return Result::err($jsonException->getMessage());
        }

        if (!is_array($decoded)) {
            return Result::err('Cannot set path on non-array/object');
        }

        // Split path and navigate to set the value
        $keys = explode('.', $path);
        $current = &$decoded;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                // Last key, set the value
                $current[$key] = $value;
            } else {
                // Navigate deeper
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }

                $current = &$current[$key];
            }
        }

        return Result::ok(new self($decoded, false));
    }

    /**
     * Remove a value at a path
     *
     * @return Result<self, string> Ok with new JSON on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function remove(string $path): Result
    {
        try {
            $decoded = $this->data();
        } catch (\JsonException $jsonException) {
            return $this->error($jsonException->getMessage());
        }

        if (!is_array($decoded)) {
            return $this->error('Cannot remove path from non-array/object');
        }

        // Split path and navigate to remove the value
        $keys = explode('.', $path);
        $current = &$decoded;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                // Last key, remove the value
                if (array_key_exists($key, $current)) {
                    unset($current[$key]);
                } else {
                    return $this->error('Path not found: ' . $path);
                }
            } else {
                // Navigate deeper
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    return $this->error('Path not found: ' . $path);
                }

                $current = &$current[$key];
            }
        }

        return Result::ok(new self($decoded, false));
    }

    // ==================== Utility ====================

    /**
     * Get the size of the JSON (number of top-level keys)
     */
    public function size(): int
    {
        try {
            $decoded = $this->data();
        } catch (\JsonException) {
            return 0;
        }

        return is_array($decoded) ? count($decoded) : 0;
    }

    /**
     * Check if the JSON is empty
     */
    public function isEmpty(): bool
    {
        try {
            $decoded = $this->data();
        } catch (\JsonException) {
            return true;
        }

        return !is_array($decoded) || $decoded === [];
    }

    /**
     * Convert to Str
     */
    #[\NoDiscard]
    public function toStr(): Str
    {
        return new Str($this->__toString());
    }

    /**
     * Convert to Arr (decodes the JSON)
     *
     * @return Result<Arr<mixed>, string> Ok with Arr on success, Err with error message on failure
     */
    #[\NoDiscard]
    public function toArr(): Result
    {
        return $this->decode();
    }
}
