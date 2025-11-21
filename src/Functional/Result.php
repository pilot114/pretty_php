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
    private function __construct(
        /** @var T */
        private mixed $value,
        /** @var E */
        private mixed $error,
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
    public static function ok(mixed $value): self
    {
        /** @var self<U, never> */
        return new self($value, null, true);
    }

    /**
     * Creates an error Result with an error value.
     *
     * @template F
     * @param F $error
     * @return self<never, F>
     */
    public static function err(mixed $error): self
    {
        /** @var self<never, F> */
        return new self(null, $error, false);
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
    public function map(callable $fn): self
    {
        if ($this->isErr()) {
            /** @var E $error */
            $error = $this->error;
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
    public function mapErr(callable $fn): self
    {
        if ($this->isOk()) {
            /** @var T $value */
            $value = $this->value;
            return self::ok($value);
        }

        /** @var E $error */
        $error = $this->error;
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
    public function andThen(callable $fn): self
    {
        if ($this->isErr()) {
            /** @var E $error */
            $error = $this->error;
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
    public function orElse(self $alternative): self
    {
        return $this->isOk() ? $this : $alternative;
    }

    /**
     * Returns the contained Ok value.
     *
     * @return T
     * @throws \RuntimeException if the result is Err
     */
    public function unwrap(): mixed
    {
        if ($this->isErr()) {
            if ($this->error instanceof \Throwable) {
                $errorMsg = $this->error->getMessage();
            } elseif ($this->error instanceof \Stringable || is_string($this->error)) {
                $errorMsg = (string) $this->error;
            } else {
                $errorMsg = var_export($this->error, true);
            }

            throw new \RuntimeException('Called unwrap on an Err value: ' . $errorMsg);
        }

        return $this->value;
    }

    /**
     * Returns the contained Err value.
     *
     * @return E
     * @throws \RuntimeException if the result is Ok
     */
    public function unwrapErr(): mixed
    {
        if ($this->isOk()) {
            throw new \RuntimeException('Called unwrapErr on an Ok value');
        }

        return $this->error;
    }

    /**
     * Returns the contained Ok value or a default.
     *
     * @param T $default
     * @return T
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->isOk()) {
            return $this->value;
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
        if ($this->isOk()) {
            return $this->value;
        }

        /** @var E $error */
        $error = $this->error;
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
        if ($this->isErr()) {
            throw new \RuntimeException($message);
        }

        return $this->value;
    }

    /**
     * Returns the contained Err value with a custom error message.
     *
     * @return E
     * @throws \RuntimeException with the provided message if the result is Ok
     */
    public function expectErr(string $message): mixed
    {
        if ($this->isOk()) {
            throw new \RuntimeException($message);
        }

        return $this->error;
    }

    /**
     * Converts from Result<T, E> to Option<T>.
     * Discards the error, if any.
     *
     * @return Option<T>
     */
    public function toOption(): Option
    {
        if ($this->isOk()) {
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
    public function toErrOption(): Option
    {
        if ($this->isErr()) {
            /** @var E $error */
            $error = $this->error;
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
        if ($this->isOk()) {
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
        if ($this->isOk()) {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        }

        /** @var E $error */
        $error = $this->error;
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
        if ($this->isOk()) {
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
        if ($this->isErr()) {
            /** @var E $error */
            $error = $this->error;
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
    public function and(self $other): self
    {
        if ($this->isOk()) {
            return $other;
        }

        /** @var E $error */
        $error = $this->error;
        return self::err($error);
    }

    /**
     * Returns this result if it is Ok, otherwise returns the other result.
     *
     * @template F
     * @param self<T, F> $other
     * @return self<T, F>
     */
    public function or(self $other): self
    {
        if ($this->isOk()) {
            /** @var T $value */
            $value = $this->value;
            return self::ok($value);
        }

        return $other;
    }
}
