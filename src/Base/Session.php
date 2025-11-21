<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Object-oriented wrapper for PHP session functions
 * Provides convenient API for session management with type safety
 */
class Session
{
    /**
     * Start a new session or resume existing one
     * Wrapper for session_start()
     *
     * @param array<string, mixed> $options Session options
     * @return bool Returns true on success
     */
    public static function start(array $options = []): bool
    {
        if (self::isActive()) {
            return true;
        }

        return session_start($options);
    }

    /**
     * Check if session is active
     *
     * @return bool True if session is active
     */
    public static function isActive(): bool
    {
        return self::status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Check if session is disabled
     *
     * @return bool True if sessions are disabled
     */
    public static function isDisabled(): bool
    {
        return self::status() === PHP_SESSION_DISABLED;
    }

    /**
     * Check if session is none (not started yet)
     *
     * @return bool True if session not started
     */
    public static function isNone(): bool
    {
        return self::status() === PHP_SESSION_NONE;
    }

    /**
     * Get current session status
     * Wrapper for session_status()
     *
     * @return int One of PHP_SESSION_DISABLED, PHP_SESSION_NONE, PHP_SESSION_ACTIVE
     */
    public static function status(): int
    {
        return session_status();
    }

    /**
     * Destroy all data registered to a session
     * Wrapper for session_destroy()
     *
     * @return bool Returns true on success
     */
    public static function destroy(): bool
    {
        if (!self::isActive()) {
            return false;
        }

        return session_destroy();
    }

    /**
     * Free all session variables
     * Wrapper for session_unset()
     */
    public static function unset(): void
    {
        if (self::isActive()) {
            session_unset();
        }
    }

    /**
     * Write session data and end session
     * Wrapper for session_write_close()
     */
    public static function close(): void
    {
        if (self::isActive()) {
            session_write_close();
        }
    }

    /**
     * Alias for close()
     * Wrapper for session_commit()
     */
    public static function commit(): void
    {
        self::close();
    }

    /**
     * Discard session array changes and finish session
     * Wrapper for session_abort()
     *
     * @return bool Returns true on success
     */
    public static function abort(): bool
    {
        if (!self::isActive()) {
            return false;
        }

        return session_abort();
    }

    /**
     * Re-initialize session array with original values
     * Wrapper for session_reset()
     *
     * @return bool Returns true on success
     */
    public static function reset(): bool
    {
        if (!self::isActive()) {
            return false;
        }

        return session_reset();
    }

    /**
     * Update the current session id with a newly generated one
     * Wrapper for session_regenerate_id()
     *
     * @param bool $deleteOldSession Whether to delete the old session file
     * @return bool Returns true on success
     */
    public static function regenerateId(bool $deleteOldSession = false): bool
    {
        if (!self::isActive()) {
            return false;
        }

        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Create new session id
     * Wrapper for session_create_id()
     *
     * @param string $prefix Prefix for the session ID
     * @return string|false New session id or false on failure
     */
    public static function createId(string $prefix = ''): string|false
    {
        return session_create_id($prefix);
    }

    /**
     * Get and/or set the current session id
     * Wrapper for session_id()
     *
     * @param string|null $id If specified, will replace the current session id
     * @return string|false Returns the session id for the current session or false on failure
     */
    public static function id(?string $id = null): string|false
    {
        if ($id !== null) {
            return session_id($id);
        }

        return session_id();
    }

    /**
     * Get and/or set the current session name
     * Wrapper for session_name()
     *
     * @param string|null $name If specified, will replace the current session name
     * @return string|false Returns the name of the current session or false on failure
     */
    public static function name(?string $name = null): string|false
    {
        if ($name !== null) {
            return session_name($name);
        }

        return session_name();
    }

    /**
     * Get and/or set the current session save path
     * Wrapper for session_save_path()
     *
     * @param string|null $path If specified, will replace the current session save path
     * @return string|false Returns the path of the current directory used for session data
     */
    public static function savePath(?string $path = null): string|false
    {
        if ($path !== null) {
            return session_save_path($path);
        }

        return session_save_path();
    }

    /**
     * Get and/or set the current session module name
     * Wrapper for session_module_name()
     *
     * @param string|null $module If specified, will replace the current session module
     * @return string|false Returns the name of the current session module
     */
    public static function moduleName(?string $module = null): string|false
    {
        if ($module !== null) {
            return session_module_name($module);
        }

        return session_module_name();
    }

    /**
     * Get and/or set the current cache limiter
     * Wrapper for session_cache_limiter()
     *
     * @param string|null $limiter If specified, will replace the current cache limiter
     * @return string|false Returns the name of the current cache limiter
     */
    public static function cacheLimiter(?string $limiter = null): string|false
    {
        if ($limiter !== null) {
            return session_cache_limiter($limiter);
        }

        return session_cache_limiter();
    }

    /**
     * Get and/or set the current cache expire
     * Wrapper for session_cache_expire()
     *
     * @param int|null $expire If specified, will replace the current cache expire time
     * @return int|false Returns the current setting of session.cache_expire
     */
    public static function cacheExpire(?int $expire = null): int|false
    {
        if ($expire !== null) {
            return session_cache_expire($expire);
        }

        return session_cache_expire();
    }

    /**
     * Get the session cookie parameters
     * Wrapper for session_get_cookie_params()
     *
     * @return array<string, mixed> Array containing current session cookie information
     */
    public static function getCookieParams(): array
    {
        return session_get_cookie_params();
    }

    /**
     * Set the session cookie parameters
     * Wrapper for session_set_cookie_params()
     *
     * @param int|array{
     *     lifetime?: int,
     *     path?: string|null,
     *     domain?: string|null,
     *     secure?: bool|null,
     *     httponly?: bool|null,
     *     samesite?: 'Lax'|'lax'|'None'|'none'|'Strict'|'strict'
     * } $lifetimeOrOptions The lifetime of the cookie in seconds, or an options array
     * @param string|null $path The path on the domain where the cookie will work
     * @param string|null $domain The cookie domain
     * @param bool|null $secure Whether cookie should only be sent over secure connections
     * @param bool|null $httponly Whether cookie is accessible only through HTTP protocol
     * @return bool Returns true on success
     */
    public static function setCookieParams(
        int|array $lifetimeOrOptions,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httponly = null
    ): bool {
        if (is_array($lifetimeOrOptions)) {
            /** @var array{lifetime?: int, path?: string|null, domain?: string|null, secure?: bool|null, httponly?: bool|null, samesite?: 'Lax'|'lax'|'None'|'none'|'Strict'|'strict'} $lifetimeOrOptions */
            return session_set_cookie_params($lifetimeOrOptions);
        }

        $params = [$lifetimeOrOptions];
        if ($path !== null) {
            $params[] = $path;
        }
        if ($domain !== null) {
            $params[] = $domain;
        }
        if ($secure !== null) {
            $params[] = $secure;
        }
        if ($httponly !== null) {
            $params[] = $httponly;
        }

        return session_set_cookie_params(...$params);
    }

    /**
     * Set user-level session storage functions
     * Wrapper for session_set_save_handler()
     *
     * @param \SessionHandlerInterface $handler A SessionHandlerInterface implementation
     * @param bool $registerShutdown Register session_write_close() as a shutdown function
     * @return bool Returns true on success
     */
    public static function setSaveHandler(\SessionHandlerInterface $handler, bool $registerShutdown = true): bool
    {
        return session_set_save_handler($handler, $registerShutdown);
    }

    /**
     * Register a shutdown function
     * Wrapper for session_register_shutdown()
     */
    public static function registerShutdown(): void
    {
        session_register_shutdown();
    }

    /**
     * Perform session data garbage collection
     * Wrapper for session_gc()
     *
     * @return int|false Returns number of deleted session data on success, false on failure
     */
    public static function gc(): int|false
    {
        return session_gc();
    }

    /**
     * Encode session data as a string
     * Wrapper for session_encode()
     *
     * @return string|false Returns the encoded data, or false on failure
     */
    public static function encode(): string|false
    {
        if (!self::isActive()) {
            return false;
        }

        return session_encode();
    }

    /**
     * Decode session data from a string
     * Wrapper for session_decode()
     *
     * @param string $data Encoded session data
     * @return bool Returns true on success
     */
    public static function decode(string $data): bool
    {
        if (!self::isActive()) {
            return false;
        }

        return session_decode($data);
    }

    // ============================================
    // High-level convenience methods
    // ============================================

    /**
     * Get a value from the session
     *
     * @param string $key The session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::isActive()) {
            self::start();
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a value in the session
     *
     * @param string $key The session key
     * @param mixed $value The value to set
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::isActive()) {
            self::start();
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Check if a key exists in the session
     *
     * @param string $key The session key
     * @return bool True if key exists
     */
    public static function has(string $key): bool
    {
        if (!self::isActive()) {
            self::start();
        }

        return array_key_exists($key, $_SESSION);
    }

    /**
     * Remove a key from the session
     *
     * @param string $key The session key
     */
    public static function remove(string $key): void
    {
        if (!self::isActive()) {
            self::start();
        }

        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     *
     * @return array<string, mixed> All session data
     */
    public static function all(): array
    {
        if (!self::isActive()) {
            self::start();
        }

        /** @var array<string, mixed> */
        return $_SESSION;
    }

    /**
     * Replace all session data
     *
     * @param array<string, mixed> $data New session data
     */
    public static function replace(array $data): void
    {
        if (!self::isActive()) {
            self::start();
        }

        $_SESSION = $data;
    }

    /**
     * Clear all session data
     */
    public static function clear(): void
    {
        if (!self::isActive()) {
            self::start();
        }

        $_SESSION = [];
    }

    /**
     * Flash data (available only for the next request)
     *
     * @param string $key The flash key
     * @param mixed $value The value to flash
     */
    public static function flash(string $key, mixed $value): void
    {
        if (!self::isActive()) {
            self::start();
        }

        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION['_flash'];
        $flash[$key] = $value;
        $_SESSION['_flash'] = $flash;
    }

    /**
     * Get flash data (and remove it)
     *
     * @param string $key The flash key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The flash value or default
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        if (!self::isActive()) {
            self::start();
        }

        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            return $default;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION['_flash'];
        $value = $flash[$key] ?? $default;
        unset($flash[$key]);
        $_SESSION['_flash'] = $flash;

        return $value;
    }

    /**
     * Check if flash data exists
     *
     * @param string $key The flash key
     * @return bool True if flash key exists
     */
    public static function hasFlash(string $key): bool
    {
        if (!self::isActive()) {
            self::start();
        }

        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            return false;
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION['_flash'];
        return isset($flash[$key]);
    }

    /**
     * Keep flash data for one more request
     *
     * @param string|array<string> $keys Flash key or keys to keep
     */
    public static function keepFlash(string|array $keys): void
    {
        if (!self::isActive()) {
            self::start();
        }

        $keys = is_array($keys) ? $keys : [$keys];

        if (!isset($_SESSION['_old_flash']) || !is_array($_SESSION['_old_flash'])) {
            return;
        }

        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        /** @var array<string, mixed> $flash */
        $flash = $_SESSION['_flash'];
        /** @var array<string, mixed> $oldFlash */
        $oldFlash = $_SESSION['_old_flash'];

        foreach ($keys as $key) {
            if (isset($oldFlash[$key])) {
                $flash[$key] = $oldFlash[$key];
            }
        }

        $_SESSION['_flash'] = $flash;
    }

    /**
     * Age flash data (move current flash to old flash)
     * Should be called at the beginning of each request
     */
    public static function ageFlashData(): void
    {
        if (!self::isActive()) {
            self::start();
        }

        if (isset($_SESSION['_flash'])) {
            $_SESSION['_old_flash'] = $_SESSION['_flash'];
            $_SESSION['_flash'] = [];
        }

        if (isset($_SESSION['_old_flash'])) {
            unset($_SESSION['_old_flash']);
        }
    }

    /**
     * Get a value and remove it from the session
     *
     * @param string $key The session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The value or default
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::remove($key);

        return $value;
    }

    /**
     * Increment a numeric session value
     *
     * @param string $key The session key
     * @param int $amount Amount to increment by
     * @return int The new value
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $currentValue = self::get($key, 0);
        if (!is_int($currentValue)) {
            $currentValue = 0;
        }

        $value = $currentValue + $amount;
        self::set($key, $value);

        return $value;
    }

    /**
     * Decrement a numeric session value
     *
     * @param string $key The session key
     * @param int $amount Amount to decrement by
     * @return int The new value
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return self::increment($key, -$amount);
    }

    /**
     * Add a value to a session array
     *
     * @param string $key The session key
     * @param mixed $value Value to add
     */
    public static function push(string $key, mixed $value): void
    {
        $array = self::get($key, []);
        if (!is_array($array)) {
            $array = [$array];
        }

        $array[] = $value;
        self::set($key, $array);
    }

    /**
     * Get and remove the last value from a session array
     *
     * @param string $key The session key
     * @return mixed The last value or null
     */
    public static function pop(string $key): mixed
    {
        $array = self::get($key, []);
        if (!is_array($array) || $array === []) {
            return null;
        }

        $value = array_pop($array);
        self::set($key, $array);

        return $value;
    }

    /**
     * Get session data as Arr instance
     *
     * @return Arr<mixed> Session data wrapped in Arr
     */
    public static function toArr(): Arr
    {
        return new Arr(self::all());
    }

    /**
     * Check if session contains any data
     *
     * @return bool True if session is empty
     */
    public static function isEmpty(): bool
    {
        return self::all() === [];
    }

    /**
     * Check if session contains any data
     *
     * @return bool True if session has data
     */
    public static function isNotEmpty(): bool
    {
        return !self::isEmpty();
    }

    /**
     * Get the session timeout setting in seconds
     *
     * @return int Timeout in seconds
     */
    public static function getTimeout(): int
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * Set the session timeout in seconds
     *
     * @param int $seconds Timeout in seconds
     */
    public static function setTimeout(int $seconds): void
    {
        ini_set('session.gc_maxlifetime', (string) $seconds);
    }

    /**
     * Check if session has expired based on last activity
     *
     * @param int $timeout Timeout in seconds (defaults to session.gc_maxlifetime)
     * @return bool True if session has expired
     */
    public static function hasExpired(int $timeout = 0): bool
    {
        if (!self::has('_last_activity')) {
            self::set('_last_activity', time());
            return false;
        }

        $lastActivity = self::get('_last_activity', 0);
        if (!is_int($lastActivity)) {
            $lastActivity = 0;
        }

        $effectiveTimeout = $timeout > 0 ? $timeout : self::getTimeout();

        if (time() - $lastActivity > $effectiveTimeout) {
            return true;
        }

        self::set('_last_activity', time());
        return false;
    }
}
