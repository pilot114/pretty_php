<?php

declare(strict_types=1);

namespace PrettyPhp;

class Str implements \Stringable
{
    public function __construct(
        private readonly string $value
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

        if ($result === false) {
            return new self('');
        }

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
     * @return Arr<string>
     */
    public function toArray(): Arr
    {
        return new Arr(mb_str_split($this->value));
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
}
