<?php

declare(strict_types=1);

namespace PrettyPhp;

use Closure;

/**
 * @template T
 */
class Arr
{
    /**
     * @param array<int|string, T> $value
     */
    public function __construct(
        private array $value
    ) {
    }

    /**
     * @return array<int|string, T>
     */
    public function get(): array
    {
        return $this->value;
    }

    /**
     * @return array<int|string, T>
     */
    public function toArray(): array
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
     */
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
     * @param array<int|string, T> $array
     */
    /**
     * @param array<int|string, T> $array
     * @return Arr<T>
     */
    public function merge(array $array): self
    {
        return new self(array_merge($this->value, $array));
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
     */
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
     */
    /**
     * @param (Closure(T): bool)|null $callback
     * @return Arr<T>
     */
    public function filter(?Closure $callback = null): self
    {
        if (!$callback instanceof \Closure) {
            return new self(array_filter($this->value));
        }

        return new self(array_filter($this->value, $callback(...)));
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
     */
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
        foreach ($this->value as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Closure(T, int|string): bool $callback
     */
    public function every(Closure $callback): bool
    {
        foreach ($this->value as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
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
        /** @var array<int|string, int|string> $flippable */
        $flippable = array_filter($this->value, static fn(mixed $value): bool => is_int($value) || is_string($value));
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
     * @param Closure(T, int|string): int|string $callback
     * @return Arr<array<int, T>>
     * @phpstan-param Closure(T, int|string): int|string $callback
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
                /** @phpstan-ignore-next-line Cannot access offset on mixed - this is validated by foreach */
                $result[] = $item;
            }
        }
    }

    public function min(): mixed
    {
        return min($this->value);
    }

    public function max(): mixed
    {
        return max($this->value);
    }

    public function sum(): int|float
    {
        return array_sum($this->value);
    }

    public function average(): float
    {
        return $this->count() > 0 ? $this->sum() / $this->count() : 0.0;
    }
}
