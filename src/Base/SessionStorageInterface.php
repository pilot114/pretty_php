<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Interface for session storage backends.
 * Allows swapping between native $_SESSION and in-memory storage for testing.
 */
interface SessionStorageInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function replace(array $data): void;

    public function clear(): void;
}
