<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

// Imported so that the engine compiles them to dedicated opcodes instead of namespaced calls
use function strlen;

readonly class Str implements \Stringable
{
    public function __construct(
        private string $value
    ) {
    }

    public function get(): string
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * An ASCII-only string has as many characters as bytes. The check costs ~45 ns when it fails, so it is
     * done only from 256 bytes, where mb_strlen() gets slower than it (measured crossover: ~150 bytes).
     */
    public function length(): int
    {
        $value = $this->value;

        return strlen($value) >= 256 && preg_match('/[\x80-\xFF]/', $value) === 0
            ? strlen($value)
            : mb_strlen($value);
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[\NoDiscard]
    public function trim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? trim($this->value)
            : trim($this->value, $characters);

        return new self($trimmed);
    }

    #[\NoDiscard]
    public function ltrim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? ltrim($this->value)
            : ltrim($this->value, $characters);

        return new self($trimmed);
    }

    #[\NoDiscard]
    public function rtrim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? rtrim($this->value)
            : rtrim($this->value, $characters);

        return new self($trimmed);
    }

    /**
     * Byte-wise strtoupper() is locale-independent and much faster than mb_strtoupper(), and gives the same
     * result for ASCII-only strings. Strings shorter than 64 bytes skip the check: there the regex costs
     * more than mb_* itself.
     */
    #[\NoDiscard]
    public function upper(): self
    {
        $value = $this->value;

        return new self(
            strlen($value) >= 64 && preg_match('/[\x80-\xFF]/', $value) === 0
                ? strtoupper($value)
                : mb_strtoupper($value)
        );
    }

    /**
     * @see upper() for the ASCII fast path
     */
    #[\NoDiscard]
    public function lower(): self
    {
        $value = $this->value;

        return new self(
            strlen($value) >= 64 && preg_match('/[\x80-\xFF]/', $value) === 0
                ? strtolower($value)
                : mb_strtolower($value)
        );
    }

    #[\NoDiscard]
    public function capitalize(): self
    {
        return new self(mb_convert_case($this->value, MB_CASE_TITLE));
    }

    public function contains(string $needle): bool
    {
        return str_contains($this->value, $needle);
    }

    public function startsWith(string $needle): bool
    {
        return str_starts_with($this->value, $needle);
    }

    public function endsWith(string $needle): bool
    {
        return str_ends_with($this->value, $needle);
    }

    #[\NoDiscard]
    public function replace(string $search, string $replace): self
    {
        return new self(str_replace($search, $replace, $this->value));
    }

    /**
     * @param array<string, string> $replacements
     */
    #[\NoDiscard]
    public function replaceAll(array $replacements): self
    {
        // Replacements are applied one after another, in array order
        return new self(str_replace(array_keys($replacements), array_values($replacements), $this->value));
    }

    /**
     * @return Arr<string>
     */
    #[\NoDiscard]
    public function split(string $delimiter, int $limit = PHP_INT_MAX): Arr
    {
        if ($delimiter === '') {
            throw new \InvalidArgumentException('Delimiter cannot be empty');
        }

        $parts = explode($delimiter, $this->value, $limit);
        return new Arr($parts);
    }

    #[\NoDiscard]
    public function substring(int $start, ?int $length = null): self
    {
        $result = $length === null
            ? mb_substr($this->value, $start)
            : mb_substr($this->value, $start, $length);

        return new self($result);
    }

    public function indexOf(string $needle, int $offset = 0): int
    {
        $position = mb_strpos($this->value, $needle, $offset);
        return $position === false ? -1 : $position;
    }

    public function lastIndexOf(string $needle, int $offset = 0): int
    {
        $position = mb_strrpos($this->value, $needle, $offset);
        return $position === false ? -1 : $position;
    }

    #[\NoDiscard]
    public function repeat(int $times): self
    {
        return new self(str_repeat($this->value, $times));
    }

    #[\NoDiscard]
    public function reverse(): self
    {
        return new self(strrev($this->value));
    }

    #[\NoDiscard]
    public function padLeft(int $length, string $padString = ' '): self
    {
        return new self(str_pad($this->value, $length, $padString, STR_PAD_LEFT));
    }

    #[\NoDiscard]
    public function padRight(int $length, string $padString = ' '): self
    {
        return new self(str_pad($this->value, $length, $padString, STR_PAD_RIGHT));
    }

    #[\NoDiscard]
    public function padBoth(int $length, string $padString = ' '): self
    {
        return new self(str_pad($this->value, $length, $padString, STR_PAD_BOTH));
    }

    public function isAlpha(): bool
    {
        return ctype_alpha($this->value);
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    public function isAlphaNumeric(): bool
    {
        return ctype_alnum($this->value);
    }

    /**
     * @return Arr<non-empty-string>
     */
    #[\NoDiscard]
    public function toArray(): Arr
    {
        return new Arr(mb_str_split($this->value));
    }

    /**
     * @return Arr<string>|null
     */
    #[\NoDiscard]
    public function match(string $pattern): ?Arr
    {
        $result = preg_match($pattern, $this->value, $matches);
        if ($result === 1) {
            return new Arr($matches);
        }

        return null;
    }

    /**
     * @return Arr<array<string>>
     */
    #[\NoDiscard]
    public function matchAll(string $pattern): Arr
    {
        preg_match_all($pattern, $this->value, $matches, PREG_SET_ORDER);
        return new Arr($matches);
    }

    /**
     * Normalize Unicode string
     * @param int $form Normalization form (Normalizer::FORM_C, FORM_D, FORM_KC, FORM_KD)
     */
    #[\NoDiscard]
    public function normalizeUnicode(int $form = \Normalizer::FORM_C): self
    {
        $normalized = \Normalizer::normalize($this->value, $form);
        if ($normalized === false) {
            throw new \InvalidArgumentException('Unicode normalization failed');
        }

        return new self($normalized);
    }

    /**
     * Generate URL-friendly slug
     */
    #[\NoDiscard]
    public function slug(string $separator = '-'): self
    {
        // Convert to lowercase
        $slug = mb_strtolower($this->value);

        // Normalize unicode characters
        $normalized = \Normalizer::normalize($slug, \Normalizer::FORM_D);
        $normalized = $normalized === false ? $slug : $normalized;

        // Remove diacritics
        $slug = (string) preg_replace('/\p{Mn}/u', '', $normalized);

        // Replace non-alphanumeric characters with separator
        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', $separator, $slug);

        // Remove leading/trailing separators
        $slug = trim($slug, $separator);

        return new self($slug);
    }

    /**
     * Truncate string with ellipsis
     */
    #[\NoDiscard]
    public function truncate(int $length, string $ellipsis = '...'): self
    {
        if ($this->length() <= $length) {
            return $this;
        }

        $ellipsisLength = mb_strlen($ellipsis);
        $truncated = mb_substr($this->value, 0, max(0, $length - $ellipsisLength)) . $ellipsis;

        return new self($truncated);
    }

    /**
     * Convert to snake_case
     */
    #[\NoDiscard]
    public function toSnakeCase(): self
    {
        // Insert underscore before uppercase letters and convert to lowercase
        $snake = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->value);
        $snake = mb_strtolower($snake);

        // Replace runs of spaces, hyphens and underscores with a single underscore
        $snake = (string) preg_replace('/[\s_-]+/', '_', $snake);

        return new self(trim($snake, '_'));
    }

    /**
     * Convert to camelCase
     */
    #[\NoDiscard]
    public function toCamelCase(): self
    {
        // Words are runs of ASCII letters and digits; the first one stays lowercase
        $words = explode(' ', trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', ' ', $this->value))));

        return new self(array_shift($words) . implode('', array_map(ucfirst(...), $words)));
    }

    /**
     * Convert to PascalCase
     */
    #[\NoDiscard]
    public function toPascalCase(): self
    {
        return new self(ucfirst($this->toCamelCase()->get()));
    }

    /**
     * Convert to kebab-case
     */
    #[\NoDiscard]
    public function toKebabCase(): self
    {
        // Insert hyphen before uppercase letters and convert to lowercase
        $kebab = (string) preg_replace('/(?<!^)[A-Z]/', '-$0', $this->value);
        $kebab = mb_strtolower($kebab);

        // Replace runs of spaces, underscores and hyphens with a single hyphen
        $kebab = (string) preg_replace('/[\s_-]+/', '-', $kebab);

        return new self(trim($kebab, '-'));
    }

    /**
     * Calculate Levenshtein distance between two strings
     */
    public function levenshtein(string $other): int
    {
        return levenshtein($this->value, $other);
    }

    /**
     * Calculate similarity percentage between two strings (0-100)
     */
    public function similarity(string $other): float
    {
        similar_text($this->value, $other, $percent);
        return $percent;
    }
}
