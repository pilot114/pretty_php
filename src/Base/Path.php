<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

use FilesystemIterator;
use RuntimeException;
use SplFileInfo;

readonly class Path implements \Stringable
{
    public function __construct(
        private string $path
    ) {
    }

    public function get(): string
    {
        return $this->path;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function isAbsolute(): bool
    {
        if ($this->path === '') {
            return false;
        }

        return $this->path[0] === '/' || (strlen($this->path) >= 3 && $this->path[1] === ':');
    }

    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    public function join(string ...$segments): self
    {
        $parts = [$this->path];
        foreach ($segments as $segment) {
            $parts[] = $segment;
        }

        return new self(implode(DIRECTORY_SEPARATOR, $parts));
    }

    public function normalize(): self
    {
        $resolved = realpath($this->path);
        return new self($resolved !== false ? $resolved : $this->path);
    }

    /**
     * @throws RuntimeException
     */
    public function resolve(): self
    {
        if ($this->isAbsolute()) {
            return $this->normalize();
        }

        $cwd = getcwd();
        if ($cwd === false) {
            throw new RuntimeException("Unable to get current working directory");
        }

        return new self($cwd)->join($this->path)->normalize();
    }

    /**
     * @throws RuntimeException
     */
    public function relative(string $to): self
    {
        $from = $this->resolve()->get();
        $to = new self($to)->resolve()->get();

        $fromParts = explode(DIRECTORY_SEPARATOR, trim($from, DIRECTORY_SEPARATOR));
        $toParts = explode(DIRECTORY_SEPARATOR, trim($to, DIRECTORY_SEPARATOR));

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativePath = str_repeat('..' . DIRECTORY_SEPARATOR, count($fromParts)) .
            implode(DIRECTORY_SEPARATOR, $toParts);

        return new self($relativePath !== '' ? $relativePath : '.');
    }

    public function parent(): self
    {
        return new self(dirname($this->path));
    }

    public function basename(): Str
    {
        return new Str(basename($this->path));
    }

    public function extension(): Str
    {
        return new Str(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function withoutExtension(): self
    {
        $pathinfo = pathinfo($this->path);
        $dir = isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '.'
            ? $pathinfo['dirname'] . DIRECTORY_SEPARATOR
            : '';
        return new self($dir . $pathinfo['filename']);
    }

    public function withExtension(string $extension): self
    {
        return $this->withoutExtension()->concat('.' . ltrim($extension, '.'));
    }

    public function concat(string $suffix): self
    {
        return new self($this->path . $suffix);
    }

    public function prepend(string $prefix): self
    {
        return new self($prefix . $this->path);
    }

    /**
     * @throws RuntimeException
     */
    public function mkdir(int $mode = 0755, bool $recursive = false): self
    {
        if (!mkdir($this->path, $mode, $recursive) && !is_dir($this->path)) {
            throw new RuntimeException('Unable to create directory: ' . $this->path);
        }

        return $this;
    }

    /**
     * @throws RuntimeException
     * @return Arr<string>
     */
    public function listFiles(): Arr
    {
        if (!is_dir($this->path)) {
            throw new RuntimeException('Path is not a directory: ' . $this->path);
        }

        $files = scandir($this->path);
        if ($files === false) {
            throw new RuntimeException('Unable to list directory: ' . $this->path);
        }

        $result = array_filter($files, fn(string $file): bool => $file !== '.' && $file !== '..');
        return new Arr(array_values($result));
    }

    /**
     * @return Arr<string>
     */
    public function glob(string $pattern): Arr
    {
        $fullPattern = $this->join($pattern)->get();
        $matches = glob($fullPattern);
        return new Arr($matches !== false ? $matches : []);
    }

    public function isFile(): bool
    {
        return is_file($this->path);
    }

    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    /**
     * @throws RuntimeException
     */
    public function size(): int
    {
        if ($this->isFile()) {
            return new File($this->path)->size();
        }

        if (!$this->isDirectory()) {
            throw new RuntimeException('Path does not exist: ' . $this->path);
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $fileSize = $file->getSize();
            if ($fileSize !== false) {
                $size += $fileSize;
            }
        }

        return $size;
    }

    public function toFile(): File
    {
        return new File($this->path);
    }

    /**
     * Improved Windows path support - normalize path separators
     */
    public function normalizePathSeparators(): self
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->path);
        return new self($normalized);
    }

    /**
     * Check if path is a Windows path
     */
    public function isWindowsPath(): bool
    {
        return strlen($this->path) >= 2 && $this->path[1] === ':' && ctype_alpha($this->path[0]);
    }

    /**
     * Check if path is a symbolic link
     */
    public function isLink(): bool
    {
        return is_link($this->path);
    }

    /**
     * Get the target of a symbolic link
     * @throws RuntimeException
     */
    public function readLink(): self
    {
        if (!$this->isLink()) {
            throw new RuntimeException('Path is not a symbolic link: ' . $this->path);
        }

        $target = readlink($this->path);
        if ($target === false) {
            throw new RuntimeException('Unable to read symbolic link: ' . $this->path);
        }

        return new self($target);
    }

    /**
     * Create a symbolic link
     * @throws RuntimeException
     */
    public function symlink(string $target): self
    {
        if (!symlink($target, $this->path)) {
            throw new RuntimeException(sprintf('Unable to create symbolic link from %s to %s', $this->path, $target));
        }

        return $this;
    }

    /**
     * Validate if path is safe (doesn't contain directory traversal)
     */
    public function isSafe(): bool
    {
        // Check for directory traversal patterns
        if (str_contains($this->path, '..')) {
            return false;
        }

        // Check for null bytes
        if (str_contains($this->path, "\0")) {
            return false;
        }

        return true;
    }

    /**
     * Validate if path format is valid
     */
    public function isValid(): bool
    {
        // Check for empty path
        if ($this->path === '') {
            return false;
        }

        // Check for null bytes
        if (str_contains($this->path, "\0")) {
            return false;
        }

        // Check for invalid characters (Windows)
        if (DIRECTORY_SEPARATOR === '\\') {
            $invalidChars = ['<', '>', '"', '|', '?', '*'];
            foreach ($invalidChars as $char) {
                if (str_contains($this->path, $char)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Convert to URL path (with forward slashes)
     */
    public function toUrlPath(): self
    {
        $urlPath = str_replace('\\', '/', $this->path);
        return new self($urlPath);
    }

    /**
     * Convert from URL path (convert forward slashes to system separator)
     */
    public static function fromUrlPath(string $urlPath): self
    {
        $systemPath = str_replace('/', DIRECTORY_SEPARATOR, $urlPath);
        return new self($systemPath);
    }

    /**
     * Encode path for URL use
     */
    public function urlEncode(): Str
    {
        $parts = explode('/', $this->toUrlPath()->get());
        $encoded = array_map('rawurlencode', $parts);
        return new Str(implode('/', $encoded));
    }

    /**
     * Recursive glob pattern matching
     * @return Arr<string>
     * @throws \UnexpectedValueException
     */
    public function globRecursive(string $pattern): Arr
    {
        $results = [];

        // Handle ** in pattern for recursive matching
        if (str_contains($pattern, '**')) {
            $parts = explode('**', $pattern, 2);
            $basePattern = rtrim($parts[0], '/');
            $remainingPattern = ltrim($parts[1] ?? '', '/');

            // Get all directories recursively
            if (is_dir($this->path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    /** @var \SplFileInfo $file */
                    $filePath = $file->getPathname();

                    if ($remainingPattern === '') {
                        $results[] = $filePath;
                    } else {
                        $globPattern = $filePath . DIRECTORY_SEPARATOR . $remainingPattern;
                        $matches = glob($globPattern);
                        if ($matches !== false) {
                            $results = array_merge($results, $matches);
                        }
                    }
                }
            }
        } else {
            // Standard glob
            $fullPattern = $this->join($pattern)->get();
            $matches = glob($fullPattern);
            if ($matches !== false) {
                $results = $matches;
            }
        }

        return new Arr(array_unique($results));
    }

    /**
     * Get real path, resolving all symbolic links
     * @throws RuntimeException
     */
    public function realPath(): self
    {
        $real = realpath($this->path);
        if ($real === false) {
            throw new RuntimeException('Unable to resolve real path: ' . $this->path);
        }

        return new self($real);
    }

    /**
     * Check if two paths point to the same location (considering symlinks)
     */
    public function isSameAs(string|self $other): bool
    {
        $otherPath = $other instanceof self ? $other->path : $other;

        try {
            $thisReal = $this->exists() ? $this->realPath()->get() : $this->path;
            $otherReal = file_exists($otherPath) ? (new self($otherPath))->realPath()->get() : $otherPath;

            return $thisReal === $otherReal;
        } catch (RuntimeException) {
            return $this->path === $otherPath;
        }
    }
}
