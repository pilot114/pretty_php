<?php

declare(strict_types=1);

namespace PrettyPhp\Functional;

/**
 * Result is a type that represents either success (Ok) or failure (Err).
 *
 * @template T
 * @template E
 */
readonly class Result
{
    /**
     * One property holds either the Ok value or the Err error: a smaller object is cheaper to create.
     */
    private function __construct(
        /** @var T|E */
        private mixed $value,
        private bool $isOk
    ) {
    }

    /**
     * Creates a success Result with a value.
     *
     * @template U
     * @param U $value
     * @return self<U, never>
     */
    #[\NoDiscard]
    public static function ok(mixed $value): self
    {
        /** @var self<U, never> */
        return new self($value, true);
    }

    /**
     * Creates an error Result with an error value.
     *
     * @template F
     * @param F $error
     * @return self<never, F>
     */
    #[\NoDiscard]
    public static function err(mixed $error): self
    {
        /** @var self<never, F> */
        return new self($error, false);
    }

    /**
     * Creates a Result from a callable that may throw an exception.
     * Returns Ok if successful, Err with the exception if it throws.
     *
     * @template U
     * @param callable(): U $fn
     * @return self<U, \Throwable>
     */
    public static function from(callable $fn): self
    {
        try {
            return self::ok($fn());
        } catch (\Throwable $throwable) {
            return self::err($throwable);
        }
    }

    /**
     * Returns true if the result is Ok.
     */
    public function isOk(): bool
    {
        return $this->isOk;
    }

    /**
     * Returns true if the result is Err.
     */
    public function isErr(): bool
    {
        return !$this->isOk;
    }

    /**
     * Maps a Result<T, E> to Result<U, E> by applying a function to the Ok value.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U, E>
     */
    #[\NoDiscard]
    public function map(callable $fn): self
    {
        if (!$this->isOk) {
            /** @var E $error */
            $error = $this->value;
            return self::err($error);
        }

        /** @var T $value */
        $value = $this->value;
        return self::ok($fn($value));
    }

    /**
     * Maps a Result<T, E> to Result<T, F> by applying a function to the Err value.
     *
     * @template F
     * @param callable(E): F $fn
     * @return self<T, F>
     */
    #[\NoDiscard]
    public function mapErr(callable $fn): self
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return self::ok($value);
        }

        /** @var E $error */
        $error = $this->value;
        return self::err($fn($error));
    }

    /**
     * Maps a Result<T, E> to Result<U, E> by applying a function that returns a Result.
     * Also known as flatMap or bind.
     *
     * @template U
     * @param callable(T): self<U, E> $fn
     * @return self<U, E>
     */
    #[\NoDiscard]
    public function andThen(callable $fn): self
    {
        if (!$this->isOk) {
            /** @var E $error */
            $error = $this->value;
            return self::err($error);
        }

        /** @var T $value */
        $value = $this->value;
        return $fn($value);
    }

    /**
     * Returns the result if it is Ok, otherwise returns the alternative.
     *
     * @param self<T, E> $alternative
     * @return self<T, E>
     */
    #[\NoDiscard]
    public function orElse(self $alternative): self
    {
        return $this->isOk ? $this : $alternative;
    }

    /**
     * Returns the contained Ok value.
     *
     * @return T
     * @throws \RuntimeException if the result is Err
     */
    public function unwrap(): mixed
    {
        if (!$this->isOk) {
            if ($this->value instanceof \Throwable) {
                $errorMsg = $this->value->getMessage();
            } elseif ($this->value instanceof \Stringable || is_string($this->value)) {
                $errorMsg = (string) $this->value;
            } else {
                $errorMsg = var_export($this->value, true);
            }

            throw new \RuntimeException('Called unwrap on an Err value: ' . $errorMsg);
        }

        /** @var T $value */
        $value = $this->value;
        return $value;
    }

    /**
     * Returns the contained Err value.
     *
     * @return E
     * @throws \RuntimeException if the result is Ok
     */
    public function unwrapErr(): mixed
    {
        if ($this->isOk) {
            throw new \RuntimeException('Called unwrapErr on an Ok value');
        }

        /** @var E $value */
        $value = $this->value;
        return $value;
    }

    /**
     * Returns the contained Ok value or a default.
     *
     * @param T $default
     * @return T
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return $value;
        }

        return $default;
    }

    /**
     * Returns the contained Ok value or computes it from a closure.
     *
     * @param callable(E): T $fn
     * @return T
     */
    public function unwrapOrElse(callable $fn): mixed
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return $value;
        }

        /** @var E $error */
        $error = $this->value;
        return $fn($error);
    }

    /**
     * Returns the contained Ok value with a custom error message.
     *
     * @return T
     * @throws \RuntimeException with the provided message if the result is Err
     */
    public function expect(string $message): mixed
    {
        if (!$this->isOk) {
            throw new \RuntimeException($message);
        }

        /** @var T $value */
        $value = $this->value;
        return $value;
    }

    /**
     * Returns the contained Err value with a custom error message.
     *
     * @return E
     * @throws \RuntimeException with the provided message if the result is Ok
     */
    public function expectErr(string $message): mixed
    {
        if ($this->isOk) {
            throw new \RuntimeException($message);
        }

        /** @var E $value */
        $value = $this->value;
        return $value;
    }

    /**
     * Converts from Result<T, E> to Option<T>.
     * Discards the error, if any.
     *
     * @return Option<T>
     */
    #[\NoDiscard]
    public function toOption(): Option
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return Option::some($value);
        }

        return Option::none();
    }

    /**
     * Converts from Result<T, E> to Option<E>.
     * Discards the success value, if any.
     *
     * @return Option<E>
     */
    #[\NoDiscard]
    public function toErrOption(): Option
    {
        if (!$this->isOk) {
            /** @var E $error */
            $error = $this->value;
            return Option::some($error);
        }

        return Option::none();
    }

    /**
     * Applies a function to the contained Ok value (if any), or returns the default (if not).
     *
     * @template U
     * @param U $default
     * @param callable(T): U $fn
     * @return U
     */
    public function mapOr(mixed $default, callable $fn): mixed
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        }

        return $default;
    }

    /**
     * Applies a function to the contained Ok value (if any), or computes a default (if not).
     *
     * @template U
     * @param callable(E): U $default
     * @param callable(T): U $fn
     * @return U
     */
    public function mapOrElse(callable $default, callable $fn): mixed
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        }

        /** @var E $error */
        $error = $this->value;
        return $default($error);
    }

    /**
     * Calls the provided closure with the contained Ok value (if Ok).
     *
     * @param callable(T): void $fn
     * @return self<T, E>
     */
    public function inspect(callable $fn): self
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            $fn($value);
        }

        return $this;
    }

    /**
     * Calls the provided closure with the contained Err value (if Err).
     *
     * @param callable(E): void $fn
     * @return self<T, E>
     */
    public function inspectErr(callable $fn): self
    {
        if (!$this->isOk) {
            /** @var E $error */
            $error = $this->value;
            $fn($error);
        }

        return $this;
    }

    /**
     * Returns the Ok value if both this and the other result are Ok.
     * Otherwise returns the first Err value.
     *
     * @template U
     * @param self<U, E> $other
     * @return self<U, E>
     */
    #[\NoDiscard]
    public function and(self $other): self
    {
        if ($this->isOk) {
            return $other;
        }

        /** @var E $error */
        $error = $this->value;
        return self::err($error);
    }

    /**
     * Returns this result if it is Ok, otherwise returns the other result.
     *
     * @template F
     * @param self<T, F> $other
     * @return self<T, F>
     */
    #[\NoDiscard]
    public function or(self $other): self
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return self::ok($value);
        }

        return $other;
    }

    /**
     * Converts from Result<Result<T, E>, E> to Result<T, E>.
     * Flattens one level of nesting.
     *
     * @return ($this is self<self<mixed, mixed>, mixed> ? self<mixed, mixed> : self<T, E>)
     */
    #[\NoDiscard]
    public function flatten(): self
    {
        // An Err or a non-nested Ok is already flat
        if ($this->isOk && $this->value instanceof self) {
            return $this->value;
        }

        return $this;
    }

    /**
     * Handles both Ok and Err branches, producing a single value.
     *
     * @template U
     * @param callable(T): U $onOk
     * @param callable(E): U $onErr
     * @return U
     */
    public function fold(callable $onOk, callable $onErr): mixed
    {
        if ($this->isOk) {
            /** @var T $value */
            $value = $this->value;
            return $onOk($value);
        }

        /** @var E $error */
        $error = $this->value;
        return $onErr($error);
    }
}
