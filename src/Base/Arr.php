<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

use Closure;

/**
 * @template T
 */
class Arr
{
    /**
     * @var array<int|string, T>
     */
    private array $value;

    /**
     * @param iterable<int|string, T> $value
     */
    public function __construct(
        iterable $value
    ) {
        if (is_array($value)) {
            $this->value = $value;
        } else {
            $array = [];
            foreach ($value as $key => $item) {
                $array[$key] = $item;
            }

            $this->value = $array;
        }
    }

    /**
     * Create Arr instance from any iterable
     * @template U
     * @param iterable<int|string, U> $iterable
     * @return self<U>
     */
    public static function from(iterable $iterable): self
    {
        if (is_array($iterable)) {
            return new self($iterable);
        }

        $array = [];
        foreach ($iterable as $key => $value) {
            $array[$key] = $value;
        }

        return new self($array);
    }

    /**
     * @return array<int|string, T>
     */
    public function get(): array
    {
        return $this->value;
    }

    public function count(): int
    {
        return count($this->value);
    }

    public function isEmpty(): bool
    {
        return $this->value === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function first(): mixed
    {
        return reset($this->value);
    }

    public function last(): mixed
    {
        return end($this->value);
    }

    /**
     * @return Arr<T>
     */
    public function push(mixed $value): self
    {
        $newArray = $this->value;
        $newArray[] = $value;
        /** @var array<int|string, T> $newArray */
        return new self($newArray);
    }

    public function pop(): mixed
    {
        $newArray = $this->value;
        return array_pop($newArray);
    }

    public function shift(): mixed
    {
        $newArray = $this->value;
        return array_shift($newArray);
    }

    /**
     * @return Arr<T>
     */
    public function unshift(mixed $value): self
    {
        $newArray = $this->value;
        array_unshift($newArray, $value);
        /** @var array<int|string, T> $newArray */
        return new self($newArray);
    }

    public function contains(mixed $needle): bool
    {
        return in_array($needle, $this->value, true);
    }

    public function indexOf(mixed $needle): int
    {
        $key = array_search($needle, $this->value, true);
        return $key === false ? -1 : (int)$key;
    }

    /**
     * @return Arr<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->value, $offset, $length));
    }

    /**
     * @param array<int|string, T> $replacement
     * @return Arr<T>
     */
    public function splice(int $offset, ?int $length = null, array $replacement = []): self
    {
        $newArray = $this->value;
        array_splice($newArray, $offset, $length, $replacement);
        return new self($newArray);
    }

    /**
     * @param iterable<int|string, T> $iterable
     * @return Arr<T>
     */
    public function merge(iterable $iterable): self
    {
        if (is_array($iterable)) {
            return new self(array_merge($this->value, $iterable));
        }

        $merged = $this->value;
        foreach ($iterable as $key => $value) {
            if (is_int($key)) {
                $merged[] = $value;
            } else {
                $merged[$key] = $value;
            }
        }

        return new self($merged);
    }

    /**
     * @return Arr<T>
     */
    public function unique(): self
    {
        return new self(array_unique($this->value));
    }

    /**
     * @return Arr<T>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->value));
    }

    /**
     * @param (Closure(T, T): int)|null $callback
     * @return Arr<T>
     */
    public function sort(?Closure $callback = null): self
    {
        $newArray = $this->value;
        if (!$callback instanceof \Closure) {
            sort($newArray);
        } else {
            usort($newArray, $callback);
        }

        return new self($newArray);
    }

    /**
     * @param (Closure(T, int|string): bool)|null $callback
     * @return Arr<T>
     */
    public function filter(?Closure $callback = null): self
    {
        if (!$callback instanceof \Closure) {
            return new self(array_filter(
                $this->value,
                fn($value): bool => $value !== null && $value !== false && $value !== '' &&
                    $value !== 0 && $value !== [],
                ARRAY_FILTER_USE_BOTH
            ));
        }

        $result = [];
        foreach ($this->value as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new self($result);
    }

    /**
     * @template U
     * @param Closure(T, int|string): U $callback
     * @return Arr<U>
     */
    public function map(Closure $callback): self
    {
        $result = [];
        foreach ($this->value as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return new self($result);
    }

    /**
     * @template U
     * @param Closure(U, T): U $callback
     * @param U $initial
     * @return U
     */
    public function reduce(Closure $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->value, $callback, $initial);
    }

    /**
     * @param Closure(T, int|string): void $callback
     * @return Arr<T>
     */
    public function each(Closure $callback): self
    {
        foreach ($this->value as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    /**
     * @param Closure(T, int|string): bool $callback
     * @return T|null
     */
    public function find(Closure $callback): mixed
    {
        foreach ($this->value as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param Closure(T, int|string): bool $callback
     */
    public function findIndex(Closure $callback): int
    {
        foreach ($this->value as $key => $value) {
            if ($callback($value, $key)) {
                return is_int($key) ? $key : -1;
            }
        }

        return -1;
    }

    /**
     * @param Closure(T, int|string): bool $callback
     */
    public function some(Closure $callback): bool
    {
        return array_any($this->value, fn($value, $key) => $callback($value, $key));
    }

    /**
     * @param Closure(T, int|string): bool $callback
     */
    public function every(Closure $callback): bool
    {
        return array_all($this->value, fn($value, $key) => $callback($value, $key));
    }

    /**
     * @return Arr<int|string>
     */
    public function keys(): self
    {
        return new self(array_keys($this->value));
    }

    /**
     * @return Arr<T>
     */
    public function values(): self
    {
        return new self(array_values($this->value));
    }

    /**
     * @return Arr<int|string>
     */
    public function flip(): self
    {
        $flippable = array_filter($this->value, fn($value): bool => is_int($value) || is_string($value));

        return new self(array_flip($flippable));
    }

    /**
     * @return Arr<array<int, T>>
     */
    public function chunk(int $size): self
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Chunk size must be at least 1');
        }

        /** @var array<int, array<int, T>> $chunked */
        $chunked = array_chunk($this->value, $size);
        return new self($chunked);
    }

    public function join(string $glue = ''): Str
    {
        return new Str(implode($glue, $this->value));
    }

    /**
     * @param Closure(T, int|string): (int|string) $callback
     * @return Arr<array<int, T>>
     */
    public function groupBy(Closure $callback): self
    {
        $groups = [];
        foreach ($this->value as $key => $value) {
            $groupKey = $callback($value, $key);
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = $value;
        }

        /** @var array<int|string, array<int, T>> $groups */
        return new self($groups);
    }

    /**
     * @return Arr<mixed>
     */
    public function flatten(int $depth = 1): self
    {
        $result = [];
        $this->flattenRecursive($this->value, $result, $depth);
        /** @var array<int|string, mixed> $result */
        return new self($result);
    }

    /**
     * @param array<mixed> $array
     * @param array<mixed> $result
     */
    private function flattenRecursive(array $array, array &$result, int $depth): void
    {
        foreach ($array as $item) {
            if (is_array($item) && $depth > 0) {
                $this->flattenRecursive($item, $result, $depth - 1);
            } else {
                $result[] = $item;
            }
        }
    }

    public function min(): mixed
    {
        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Cannot get minimum of empty array');
        }

        /** @var non-empty-array<int|string, T> $nonEmptyValue */
        $nonEmptyValue = $this->value;
        return min($nonEmptyValue);
    }

    public function max(): mixed
    {
        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Cannot get maximum of empty array');
        }

        /** @var non-empty-array<int|string, T> $nonEmptyValue */
        $nonEmptyValue = $this->value;
        return max($nonEmptyValue);
    }

    public function sum(): int|float
    {
        return array_sum($this->value);
    }

    public function average(): float
    {
        return $this->count() > 0 ? $this->sum() / $this->count() : 0.0;
    }

    /**
     * Partition array into two arrays based on a predicate
     * @param Closure(T, int|string): bool $callback
     * @return Arr<array<int, T>>
     */
    public function partition(Closure $callback): self
    {
        $pass = [];
        $fail = [];

        foreach ($this->value as $key => $value) {
            if ($callback($value, $key)) {
                $pass[] = $value;
            } else {
                $fail[] = $value;
            }
        }

        /** @var array<int, array<int, T>> $result */
        $result = [$pass, $fail];
        return new self($result);
    }

    /**
     * Zip multiple arrays together
     * @param iterable<int|string, mixed> ...$arrays
     * @return Arr<array<int, mixed>>
     */
    public function zip(iterable ...$arrays): self
    {
        $allArrays = [$this->value];
        foreach ($arrays as $array) {
            $allArrays[] = is_array($array) ? $array : iterator_to_array($array);
        }

        $maxLength = max(array_map('count', $allArrays));
        $result = [];

        for ($i = 0; $i < $maxLength; $i++) {
            $tuple = [];
            foreach ($allArrays as $array) {
                $tuple[] = array_values($array)[$i] ?? null;
            }

            $result[] = $tuple;
        }

        /** @var array<int, array<int, mixed>> $result */
        return new self($result);
    }

    /**
     * Unzip an array of tuples into separate arrays
     * @return Arr<array<int, mixed>>
     */
    public function unzip(): self
    {
        if ($this->isEmpty()) {
            return new self([]);
        }

        $result = [];
        foreach ($this->value as $tuple) {
            if (!is_array($tuple)) {
                continue;
            }

            foreach ($tuple as $index => $value) {
                if (!isset($result[$index])) {
                    $result[$index] = [];
                }

                $result[$index][] = $value;
            }
        }

        /** @var array<int, array<int, mixed>> $result */
        return new self($result);
    }

    /**
     * Get the difference between this array and one or more arrays
     * @param iterable<int|string, T> ...$arrays
     * @return Arr<T>
     */
    public function difference(iterable ...$arrays): self
    {
        $phpArrays = [];
        foreach ($arrays as $array) {
            $phpArrays[] = is_array($array) ? $array : iterator_to_array($array);
        }

        return new self(array_diff($this->value, ...$phpArrays));
    }

    /**
     * Get the intersection between this array and one or more arrays
     * @param iterable<int|string, T> ...$arrays
     * @return Arr<T>
     */
    public function intersection(iterable ...$arrays): self
    {
        $phpArrays = [];
        foreach ($arrays as $array) {
            $phpArrays[] = is_array($array) ? $array : iterator_to_array($array);
        }

        return new self(array_intersect($this->value, ...$phpArrays));
    }

    /**
     * Get the union of this array and one or more arrays (unique values)
     * @param iterable<int|string, T> ...$arrays
     * @return Arr<T>
     */
    public function union(iterable ...$arrays): self
    {
        $result = $this->value;

        foreach ($arrays as $array) {
            $phpArray = is_array($array) ? $array : iterator_to_array($array);
            $result = array_merge($result, $phpArray);
        }

        return new self(array_unique($result));
    }

    /**
     * Sort by multiple keys
     * @param array<string, 'asc'|'desc'> $keys Array of key => direction pairs
     * @return Arr<T>
     */
    public function sortByKeys(array $keys): self
    {
        $newArray = $this->value;

        usort($newArray, function ($a, $b) use ($keys): int {
            foreach ($keys as $key => $direction) {
                $aVal = null;
                $bVal = null;

                if (is_array($a)) {
                    $aVal = $a[$key] ?? null;
                } elseif (is_object($a) && property_exists($a, (string) $key)) {
                    /** @phpstan-ignore property.dynamicName */
                    $aVal = $a->{$key};
                }

                if (is_array($b)) {
                    $bVal = $b[$key] ?? null;
                } elseif (is_object($b) && property_exists($b, (string) $key)) {
                    /** @phpstan-ignore property.dynamicName */
                    $bVal = $b->{$key};
                }

                $cmp = $aVal <=> $bVal;
                if ($cmp !== 0) {
                    return $direction === 'desc' ? -$cmp : $cmp;
                }
            }

            return 0;
        });

        return new self($newArray);
    }

    /**
     * Pluck values from array of arrays/objects by key
     * @return Arr<mixed>
     */
    public function pluck(string|int $key): self
    {
        $result = [];
        $stringKey = (string) $key;

        foreach ($this->value as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $result[] = $item[$key];
            } elseif (is_object($item) && property_exists($item, $stringKey)) {
                /** @phpstan-ignore property.dynamicName */
                $result[] = $item->{$stringKey};
            }
        }

        /** @var array<int|string, mixed> $result */
        return new self($result);
    }
}
