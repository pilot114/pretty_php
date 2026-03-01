<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * In-memory session storage for testing purposes.
 * Does not require an active PHP session.
 */
class ArraySessionStorage implements SessionStorageInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    #[\Override]
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    #[\Override]
    public function replace(array $data): void
    {
        $this->data = $data;
    }

    #[\Override]
    public function clear(): void
    {
        $this->data = [];
    }
}
