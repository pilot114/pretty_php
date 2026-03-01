<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Native session storage using $_SESSION superglobal.
 */
class NativeSessionStorage implements SessionStorageInterface
{
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    #[\Override]
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function all(): array
    {
        /** @var array<string, mixed> */
        return $_SESSION;
    }

    /**
     * @param array<string, mixed> $data
     */
    #[\Override]
    public function replace(array $data): void
    {
        $_SESSION = $data;
    }

    #[\Override]
    public function clear(): void
    {
        $_SESSION = [];
    }
}
