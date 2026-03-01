<?php

declare(strict_types=1);

namespace PrettyPhp\Functional;

/**
 * TryResult is a specialized Result<T, Throwable> with safe mapping.
 * Unlike Result::map(), TryResult::map() automatically catches exceptions from the callback.
 *
 * @template T
 */
readonly class TryResult
{
    private function __construct(
        /** @var T */
        private mixed $value,
        private ?\Throwable $error,
        private bool $isSuccess
    ) {
    }

    /**
     * Create a TryResult by executing a callable that may throw.
     *
     * @template U
     * @param callable(): U $fn
     * @return self<U>
     */
    public static function of(callable $fn): self
    {
        try {
            return self::success($fn());
        } catch (\Throwable $throwable) {
            return self::failure($throwable);
        }
    }

    /**
     * Create a successful TryResult.
     *
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function success(mixed $value): self
    {
        return new self($value, null, true);
    }

    /**
     * Create a failed TryResult.
     *
     * @return self<never>
     */
    public static function failure(\Throwable $error): self
    {
        /** @var self<never> */
        return new self(null, $error, false);
    }

    /**
     * Returns true if this is a success.
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * Returns true if this is a failure.
     */
    public function isFailure(): bool
    {
        return !$this->isSuccess;
    }

    /**
     * Safe map: applies a function to the success value.
     * If the function throws, the result becomes a Failure.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        if ($this->isFailure()) {
            /** @var self<U> */
            return new self(null, $this->error, false);
        }

        try {
            /** @var T $value */
            $value = $this->value;
            return self::success($fn($value));
        } catch (\Throwable $throwable) {
            return self::failure($throwable);
        }
    }

    /**
     * Safe flatMap: applies a function that returns a TryResult.
     * If the function throws, the result becomes a Failure.
     *
     * @template U
     * @param callable(T): self<U> $fn
     * @return self<U>
     */
    public function flatMap(callable $fn): self
    {
        if ($this->isFailure()) {
            /** @var self<U> */
            return new self(null, $this->error, false);
        }

        try {
            /** @var T $value */
            $value = $this->value;
            return $fn($value);
        } catch (\Throwable $throwable) {
            return self::failure($throwable);
        }
    }

    /**
     * Recover from a failure by applying a function.
     * If this is a success, returns self unchanged.
     * If this is a failure, applies the function to the error to produce a success value.
     *
     * @param callable(\Throwable): T $fn
     * @return self<T>
     */
    public function recover(callable $fn): self
    {
        if ($this->isSuccess()) {
            return $this;
        }

        try {
            /** @var \Throwable $error */
            $error = $this->error;
            return self::success($fn($error));
        } catch (\Throwable $throwable) {
            return self::failure($throwable);
        }
    }

    /**
     * Recover from a failure by applying a function that returns a TryResult.
     * If this is a success, returns self unchanged.
     *
     * @param callable(\Throwable): self<T> $fn
     * @return self<T>
     */
    public function recoverWith(callable $fn): self
    {
        if ($this->isSuccess()) {
            return $this;
        }

        try {
            /** @var \Throwable $error */
            $error = $this->error;
            return $fn($error);
        } catch (\Throwable $throwable) {
            return self::failure($throwable);
        }
    }

    /**
     * Get the success value.
     *
     * @return T
     * @throws \RuntimeException if this is a failure
     */
    public function get(): mixed
    {
        if ($this->isFailure()) {
            throw new \RuntimeException(
                'Called get on a Failure: ' . ($this->error !== null ? $this->error->getMessage() : 'unknown error'),
                0,
                $this->error
            );
        }

        return $this->value;
    }

    /**
     * Get the success value or a default.
     *
     * @param T $default
     * @return T
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->isSuccess() ? $this->value : $default;
    }

    /**
     * Get the error if this is a failure.
     */
    public function getError(): ?\Throwable
    {
        return $this->error;
    }

    /**
     * Handle both success and failure branches, producing a single value.
     *
     * @template U
     * @param callable(T): U $onSuccess
     * @param callable(\Throwable): U $onFailure
     * @return U
     */
    public function fold(callable $onSuccess, callable $onFailure): mixed
    {
        if ($this->isSuccess()) {
            /** @var T $value */
            $value = $this->value;
            return $onSuccess($value);
        }

        /** @var \Throwable $error */
        $error = $this->error;
        return $onFailure($error);
    }

    /**
     * Convert to a Result<T, Throwable>.
     *
     * @return Result<T, \Throwable>
     */
    public function toResult(): Result
    {
        if ($this->isSuccess()) {
            /** @var T $value */
            $value = $this->value;
            return Result::ok($value);
        }

        /** @var \Throwable $error */
        $error = $this->error;
        return Result::err($error);
    }

    /**
     * Convert to an Option<T>.
     * Success becomes Some, Failure becomes None.
     *
     * @return Option<T>
     */
    public function toOption(): Option
    {
        if ($this->isSuccess()) {
            /** @var T $value */
            $value = $this->value;
            return Option::some($value);
        }

        return Option::none();
    }
}
