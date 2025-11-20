<?php

declare(strict_types=1);

namespace PrettyPhp\Functional;

/**
 * Represents an optional value: every Option is either Some and contains a value, or None and does not.
 *
 * @template T
 */
readonly class Option
{
    private function __construct(
        /** @var T */
        private mixed $value,
        private bool $isSome
    ) {
    }

    /**
     * Creates an Option with a value (Some).
     *
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function some(mixed $value): self
    {
        return new self($value, true);
    }

    /**
     * Creates an Option without a value (None).
     *
     * @return self<never>
     */
    public static function none(): self
    {
        /** @var self<never> */
        return new self(null, false);
    }

    /**
     * Creates an Option from a nullable value.
     * Returns Some if the value is not null, None otherwise.
     *
     * @template U
     * @param U|null $value
     * @return self<U>
     */
    public static function from(mixed $value): self
    {
        return $value !== null ? self::some($value) : self::none();
    }

    /**
     * Returns true if the option is a Some value.
     */
    public function isSome(): bool
    {
        return $this->isSome;
    }

    /**
     * Returns true if the option is a None value.
     */
    public function isNone(): bool
    {
        return !$this->isSome;
    }

    /**
     * Maps an Option<T> to Option<U> by applying a function to the contained value.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var T $value */
        $value = $this->value;
        return self::some($fn($value));
    }

    /**
     * Maps an Option<T> to Option<U> by applying a function that returns an Option.
     * Also known as flatMap or bind.
     *
     * @template U
     * @param callable(T): self<U> $fn
     * @return self<U>
     */
    public function andThen(callable $fn): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var T $value */
        $value = $this->value;
        return $fn($value);
    }

    /**
     * Returns the option if it contains a value that satisfies the predicate,
     * otherwise returns None.
     *
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var T $value */
        $value = $this->value;
        return $predicate($value) ? $this : self::none();
    }

    /**
     * Returns this option if it contains a value, otherwise returns the alternative.
     *
     * @param self<T> $alternative
     * @return self<T>
     */
    public function orElse(self $alternative): self
    {
        return $this->isSome() ? $this : $alternative;
    }

    /**
     * Returns the contained value.
     *
     * @return T
     * @throws \RuntimeException if the value is None
     */
    public function unwrap(): mixed
    {
        if ($this->isNone()) {
            throw new \RuntimeException('Called unwrap on a None value');
        }

        /** @var T */
        return $this->value;
    }

    /**
     * Returns the contained value or a default.
     *
     * @param T $default
     * @return T
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->isSome()) {
            /** @var T */
            return $this->value;
        }

        return $default;
    }

    /**
     * Returns the contained value or computes it from a closure.
     *
     * @param callable(): T $fn
     * @return T
     */
    public function unwrapOrElse(callable $fn): mixed
    {
        if ($this->isSome()) {
            /** @var T */
            return $this->value;
        }

        return $fn();
    }

    /**
     * Returns the contained value with a custom error message.
     *
     * @return T
     * @throws \RuntimeException with the provided message if the value is None
     */
    public function expect(string $message): mixed
    {
        if ($this->isNone()) {
            throw new \RuntimeException($message);
        }

        /** @var T */
        return $this->value;
    }

    /**
     * Converts from Option<T> to T|null.
     *
     * @return T|null
     */
    public function toNullable(): mixed
    {
        if ($this->isSome()) {
            /** @var T */
            return $this->value;
        }

        return null;
    }

    /**
     * Applies a function to the contained value (if any), or returns the default (if not).
     *
     * @template U
     * @param U $default
     * @param callable(T): U $fn
     * @return U
     */
    public function mapOr(mixed $default, callable $fn): mixed
    {
        if ($this->isSome()) {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        }

        return $default;
    }

    /**
     * Applies a function to the contained value (if any), or computes a default (if not).
     *
     * @template U
     * @param callable(): U $default
     * @param callable(T): U $fn
     * @return U
     */
    public function mapOrElse(callable $default, callable $fn): mixed
    {
        if ($this->isSome()) {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        }

        return $default();
    }

    /**
     * Returns Some if exactly one of this and the other option is Some, otherwise returns None.
     *
     * @param self<T> $other
     * @return self<T>
     */
    public function xor(self $other): self
    {
        if ($this->isSome() && $other->isNone()) {
            return $this;
        }

        if ($this->isNone() && $other->isSome()) {
            return $other;
        }

        return self::none();
    }

    /**
     * Calls the provided closure with the contained value (if Some).
     *
     * @param callable(T): void $fn
     * @return self<T>
     */
    public function inspect(callable $fn): self
    {
        if ($this->isSome()) {
            /** @var T $value */
            $value = $this->value;
            $fn($value);
        }

        return $this;
    }
}
