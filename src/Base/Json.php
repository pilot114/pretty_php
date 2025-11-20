<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

use PrettyPhp\Functional\Result;

readonly class Json implements \Stringable
{
    /**
     * @param mixed $value The JSON string or data to encode
     * @param bool $isEncoded Whether the value is already a JSON string
     */
    private function __construct(
        private mixed $value,
        private bool $isEncoded = false
    ) {
    }

    /**
     * Create from a JSON string
     */
    public static function fromString(string $json): self
    {
        return new self($json, true);
    }

    /**
     * Create from data to be encoded
     *
     * @param mixed $data
     */
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

        if (!is_string($this->value)) {
            return false;
        }

        json_decode($this->value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate and return Result
     *
     * @return Result<self, string> Ok with self on success, Err with error message on failure
     */
    public function validate(): Result
    {
        if (!$this->isEncoded) {
            json_encode($this->value);
        } else {
            if (!is_string($this->value)) {
                return Result::err('Invalid JSON: value is not a string');
            }
            json_decode($this->value);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Result::err(json_last_error_msg());
        }

        /** @phpstan-ignore return.type */
        return Result::ok($this);
    }

    // ==================== Formatting ====================

    /**
     * Pretty print JSON with indentation
     */
    public function pretty(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): self
    {
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return $this;
            }
            // Decode and re-encode with pretty print
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this;
            }
            $pretty = json_encode($decoded, $flags);
        } else {
            $pretty = json_encode($this->value, $flags);
        }

        if ($pretty === false) {
            return $this;
        }

        return new self($pretty, true);
    }

    /**
     * Minify JSON by removing whitespace
     */
    public function minify(): self
    {
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return $this;
            }
            // Decode and re-encode without pretty print
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this;
            }
            $minified = json_encode($decoded);
        } else {
            $minified = json_encode($this->value);
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
    public function path(string $path): Result
    {
        // Decode if needed
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return Result::err("Invalid JSON: value is not a string");
            }
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return Result::err(json_last_error_msg());
            }
        } else {
            $decoded = $this->value;
        }

        // Split path by dots
        $keys = explode('.', $path);
        $current = $decoded;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_object($current) && isset($current->$key)) {
                /** @phpstan-ignore property.dynamicName */
                $current = $current->$key;
            } else {
                /** @phpstan-ignore return.type */
                return Result::err("Path not found: {$path}");
            }
        }

        return Result::ok($current);
    }

    /**
     * Check if a path exists in the JSON
     */
    public function hasPath(string $path): bool
    {
        return $this->path($path)->isOk();
    }

    // ==================== Manipulation ====================

    /**
     * Merge with another JSON object
     *
     * @return Result<self, string> Ok with merged JSON on success, Err with error message on failure
     */
    public function merge(self $other): Result
    {
        // Decode both
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return Result::err('Failed to decode this JSON: value is not a string');
            }
            $thisDecoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                /** @phpstan-ignore return.type */
                return Result::err('Failed to decode this JSON: ' . json_last_error_msg());
            }
        } else {
            $thisDecoded = $this->value;
        }

        if ($other->isEncoded) {
            if (!is_string($other->value)) {
                return Result::err('Failed to decode other JSON: value is not a string');
            }
            $otherDecoded = json_decode($other->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                /** @phpstan-ignore return.type */
                return Result::err('Failed to decode other JSON: ' . json_last_error_msg());
            }
        } else {
            $otherDecoded = $other->value;
        }

        // Merge arrays recursively
        if (!is_array($thisDecoded) || !is_array($otherDecoded)) {
            return Result::err('Both values must be arrays/objects to merge');
        }

        $merged = array_merge_recursive($thisDecoded, $otherDecoded);

        return Result::ok(new self($merged, false));
    }

    /**
     * Set a value at a path
     *
     * @return Result<self, string> Ok with new JSON on success, Err with error message on failure
     */
    public function set(string $path, mixed $value): Result
    {
        // Decode if needed
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return Result::err('Invalid JSON: value is not a string');
            }
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return Result::err(json_last_error_msg());
            }
        } else {
            $decoded = $this->value;
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
    public function remove(string $path): Result
    {
        // Decode if needed
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return Result::err('Invalid JSON: value is not a string');
            }
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return Result::err(json_last_error_msg());
            }
        } else {
            $decoded = $this->value;
        }

        if (!is_array($decoded)) {
            return Result::err('Cannot remove path from non-array/object');
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
                    /** @phpstan-ignore return.type */
                    return Result::err("Path not found: {$path}");
                }
            } else {
                // Navigate deeper
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    /** @phpstan-ignore return.type */
                    return Result::err("Path not found: {$path}");
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
        if ($this->isEncoded) {
            if (!is_string($this->value)) {
                return 0;
            }
            $decoded = json_decode($this->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return 0;
            }
        } else {
            $decoded = $this->value;
        }

        if (is_array($decoded)) {
            return count($decoded);
        }

        return 0;
    }

    /**
     * Check if the JSON is empty
     */
    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    /**
     * Convert to Str
     */
    public function toStr(): Str
    {
        return new Str($this->__toString());
    }

    /**
     * Convert to Arr (decodes the JSON)
     *
     * @return Result<Arr<mixed>, string> Ok with Arr on success, Err with error message on failure
     */
    public function toArr(): Result
    {
        return $this->decode();
    }
}
