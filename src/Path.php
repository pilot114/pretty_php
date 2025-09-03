<?php

declare(strict_types=1);

namespace PrettyPhp;

use RuntimeException;

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
        $to = (new self($to))->resolve()->get();

        $fromParts = explode(DIRECTORY_SEPARATOR, trim($from, DIRECTORY_SEPARATOR));
        $toParts = explode(DIRECTORY_SEPARATOR, trim($to, DIRECTORY_SEPARATOR));

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativePath = str_repeat('..' . DIRECTORY_SEPARATOR, count($fromParts)) . implode(DIRECTORY_SEPARATOR, $toParts);

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
        $dir = isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '.' ? $pathinfo['dirname'] . DIRECTORY_SEPARATOR : '';
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

        $result = array_filter($files, fn($file): bool => $file !== '.' && $file !== '..');
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
            return (new File($this->path))->size();
        }

        if (!$this->isDirectory()) {
            throw new RuntimeException('Path does not exist: ' . $this->path);
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $size += $file->getSize();
        }

        return $size;
    }

    public function toFile(): File
    {
        return new File($this->path);
    }
}
