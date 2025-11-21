<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

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

    public function length(): int
    {
        return mb_strlen($this->value);
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function trim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? trim($this->value)
            : trim($this->value, $characters);

        return new self($trimmed);
    }

    public function ltrim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? ltrim($this->value)
            : ltrim($this->value, $characters);

        return new self($trimmed);
    }

    public function rtrim(?string $characters = null): self
    {
        $trimmed = $characters === null
            ? rtrim($this->value)
            : rtrim($this->value, $characters);

        return new self($trimmed);
    }

    public function upper(): self
    {
        return new self(mb_strtoupper($this->value));
    }

    public function lower(): self
    {
        return new self(mb_strtolower($this->value));
    }

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

    public function replace(string $search, string $replace): self
    {
        return new self(str_replace($search, $replace, $this->value));
    }

    /**
     * @param array<string, string> $replacements
     */
    public function replaceAll(array $replacements): self
    {
        $result = $this->value;
        foreach ($replacements as $search => $replace) {
            $result = str_replace((string) $search, (string) $replace, $result);
        }

        return new self($result);
    }

    /**
     * @return Arr<string>
     */
    public function split(string $delimiter, int $limit = PHP_INT_MAX): Arr
    {
        if ($delimiter === '') {
            throw new \InvalidArgumentException('Delimiter cannot be empty');
        }

        $parts = explode($delimiter, $this->value, $limit);
        return new Arr($parts);
    }

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

    public function repeat(int $times): self
    {
        return new self(str_repeat($this->value, $times));
    }

    public function reverse(): self
    {
        return new self(strrev($this->value));
    }

    public function padLeft(int $length, string $padString = ' '): self
    {
        return new self(str_pad($this->value, $length, $padString, STR_PAD_LEFT));
    }

    public function padRight(int $length, string $padString = ' '): self
    {
        return new self(str_pad($this->value, $length, $padString, STR_PAD_RIGHT));
    }

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
        return $this->value !== '' && is_numeric($this->value);
    }

    public function isAlphaNumeric(): bool
    {
        return ctype_alnum($this->value);
    }

    /**
     * @return Arr<non-empty-string>
     */
    public function toArray(): Arr
    {
        $parts = mb_str_split($this->value);
        return new Arr(array_map('strval', $parts));
    }

    /**
     * @return Arr<string>|null
     */
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
    public function matchAll(string $pattern): Arr
    {
        preg_match_all($pattern, $this->value, $matches, PREG_SET_ORDER);
        return new Arr($matches);
    }

    /**
     * Normalize Unicode string
     * @param int $form Normalization form (Normalizer::FORM_C, FORM_D, FORM_KC, FORM_KD)
     */
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
    public function slug(string $separator = '-'): self
    {
        // Convert to lowercase
        $slug = mb_strtolower($this->value);

        // Normalize unicode characters
        $normalized = \Normalizer::normalize($slug, \Normalizer::FORM_D);
        if ($normalized === false) {
            $normalized = $slug;
        }

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
    public function toSnakeCase(): self
    {
        // Insert underscore before uppercase letters and convert to lowercase
        $snake = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->value);
        $snake = mb_strtolower($snake);

        // Replace spaces and hyphens with underscores
        $snake = (string) preg_replace('/[\s-]+/', '_', $snake);

        // Remove multiple underscores
        $snake = (string) preg_replace('/_+/', '_', $snake);

        return new self(trim($snake, '_'));
    }

    /**
     * Convert to camelCase
     */
    public function toCamelCase(): self
    {
        // Replace non-alphanumeric with spaces
        $str = (string) preg_replace('/[^a-zA-Z0-9]+/', ' ', $this->value);

        // Capitalize first letter of each word except the first
        $str = mb_strtolower($str);

        $words = explode(' ', $str);
        $result = $words[0] ?? '';
        $counter = count($words);

        for ($i = 1; $i < $counter; $i++) {
            if ($words[$i] !== '') {
                $result .= mb_convert_case($words[$i], MB_CASE_TITLE);
            }
        }

        return new self($result);
    }

    /**
     * Convert to PascalCase
     */
    public function toPascalCase(): self
    {
        $camel = $this->toCamelCase()->get();
        if ($camel === '') {
            return new self('');
        }

        $first = mb_substr($camel, 0, 1);
        $rest = mb_substr($camel, 1);

        return new self(mb_strtoupper($first) . $rest);
    }

    /**
     * Convert to kebab-case
     */
    public function toKebabCase(): self
    {
        // Insert hyphen before uppercase letters and convert to lowercase
        $kebab = (string) preg_replace('/(?<!^)[A-Z]/', '-$0', $this->value);
        $kebab = mb_strtolower($kebab);

        // Replace spaces and underscores with hyphens
        $kebab = (string) preg_replace('/[\s_]+/', '-', $kebab);

        // Remove multiple hyphens
        $kebab = (string) preg_replace('/-+/', '-', $kebab);

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
