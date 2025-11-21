<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

use RuntimeException;

readonly class File
{
    public function __construct(
        private string $path
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function isFile(): bool
    {
        return is_file($this->path);
    }

    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    public function isReadable(): bool
    {
        return is_readable($this->path);
    }

    public function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * @throws RuntimeException
     */
    public function size(): int
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $size = filesize($this->path);
        if ($size === false) {
            throw new RuntimeException('Unable to get file size: ' . $this->path);
        }

        return $size;
    }

    /**
     * @throws RuntimeException
     */
    public function lastModified(): int
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $time = filemtime($this->path);
        if ($time === false) {
            throw new RuntimeException('Unable to get modification time: ' . $this->path);
        }

        return $time;
    }

    /**
     * @throws RuntimeException
     */
    public function read(): Str
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $content = file_get_contents($this->path);
        if ($content === false) {
            throw new RuntimeException('Unable to read file: ' . $this->path);
        }

        return new Str($content);
    }

    /**
     * @throws RuntimeException
     * @return Arr<string>
     */
    public function readLines(): Arr
    {
        $str = $this->read();
        return $str->split("\n");
    }

    /**
     * Read file line by line using a generator for memory efficiency
     *
     * @throws RuntimeException
     * @return \Generator<int, string>
     */
    public function readLinesGenerator(): \Generator
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $handle = fopen($this->path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to open file: ' . $this->path);
        }

        try {
            while (($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function write(string $content): self
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            throw new RuntimeException('Directory does not exist: ' . $dir);
        }

        $result = file_put_contents($this->path, $content);
        if ($result === false) {
            throw new RuntimeException('Unable to write file: ' . $this->path);
        }

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function append(string $content): self
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            throw new RuntimeException('Directory does not exist: ' . $dir);
        }

        $result = file_put_contents($this->path, $content, FILE_APPEND);
        if ($result === false) {
            throw new RuntimeException('Unable to append to file: ' . $this->path);
        }

        return $this;
    }

    public function delete(): bool
    {
        if (!$this->exists()) {
            return true;
        }

        if ($this->isDirectory()) {
            return rmdir($this->path);
        }

        return unlink($this->path);
    }

    /**
     * @throws RuntimeException
     */
    public function copy(string $destination): File
    {
        if (!$this->exists()) {
            throw new RuntimeException('Source file does not exist: ' . $this->path);
        }

        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            throw new RuntimeException('Destination directory does not exist: ' . $destinationDir);
        }

        $result = copy($this->path, $destination);
        if (!$result) {
            throw new RuntimeException(sprintf('Unable to copy file from %s to %s', $this->path, $destination));
        }

        return new File($destination);
    }

    /**
     * @throws RuntimeException
     */
    public function move(string $destination): File
    {
        if (!$this->exists()) {
            throw new RuntimeException('Source file does not exist: ' . $this->path);
        }

        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            throw new RuntimeException('Destination directory does not exist: ' . $destinationDir);
        }

        $result = rename($this->path, $destination);
        if (!$result) {
            throw new RuntimeException(sprintf('Unable to move file from %s to %s', $this->path, $destination));
        }

        return new File($destination);
    }

    public function extension(): Str
    {
        return new Str(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function basename(): Str
    {
        return new Str(basename($this->path));
    }

    public function dirname(): Path
    {
        return new Path(dirname($this->path));
    }

    /**
     * @throws RuntimeException
     */
    public function mimeType(): Str
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $mimeType = mime_content_type($this->path);
        if ($mimeType === false) {
            throw new RuntimeException('Unable to determine MIME type: ' . $this->path);
        }

        return new Str($mimeType);
    }

    /**
     * @throws RuntimeException
     */
    public function permissions(): int
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $perms = fileperms($this->path);
        if ($perms === false) {
            throw new RuntimeException('Unable to get file permissions: ' . $this->path);
        }

        return $perms;
    }

    /**
     * @throws RuntimeException
     */
    public function chmod(int $mode): self
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $result = chmod($this->path, $mode);
        if (!$result) {
            throw new RuntimeException('Unable to change file permissions: ' . $this->path);
        }

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function touch(?int $time = null, ?int $atime = null): self
    {
        $result = touch($this->path, $time, $atime);
        if (!$result) {
            throw new RuntimeException('Unable to touch file: ' . $this->path);
        }

        return $this;
    }

    /**
     * Write file atomically (write to temp file, then rename)
     * @throws RuntimeException
     */
    public function writeAtomic(string $content): self
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            throw new RuntimeException('Directory does not exist: ' . $dir);
        }

        $tempFile = tempnam($dir, 'atomic_');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary file in: ' . $dir);
        }

        try {
            $result = file_put_contents($tempFile, $content);
            if ($result === false) {
                throw new RuntimeException('Unable to write to temporary file: ' . $tempFile);
            }

            if (!rename($tempFile, $this->path)) {
                throw new RuntimeException(sprintf('Unable to rename %s to %s', $tempFile, $this->path));
            }
        } catch (\Throwable $throwable) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }

            throw $throwable;
        }

        return $this;
    }

    /**
     * Lock file for exclusive access and execute callback
     * @template TReturn
     * @param \Closure(resource): TReturn $callback
     * @param bool $exclusive Whether to use exclusive lock (default true)
     * @return TReturn
     * @throws RuntimeException
     */
    public function withLock(\Closure $callback, bool $exclusive = true): mixed
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $handle = fopen($this->path, $exclusive ? 'r+' : 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to open file: ' . $this->path);
        }

        try {
            $lockType = $exclusive ? LOCK_EX : LOCK_SH;
            if (!flock($handle, $lockType)) {
                throw new RuntimeException('Unable to lock file: ' . $this->path);
            }

            return $callback($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Read file in chunks using a stream
     * @param int<1, max> $chunkSize Size of each chunk in bytes
     * @return \Generator<int, string>
     * @throws RuntimeException
     */
    public function readStream(int $chunkSize = 8192): \Generator
    {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException('Chunk size must be at least 1');
        }

        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $handle = fopen($this->path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to open file: ' . $this->path);
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false) {
                    throw new RuntimeException('Unable to read from file: ' . $this->path);
                }

                if ($chunk !== '') {
                    yield $chunk;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Write to file using a stream
     * @param iterable<string> $chunks
     * @throws RuntimeException
     */
    public function writeStream(iterable $chunks): self
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            throw new RuntimeException('Directory does not exist: ' . $dir);
        }

        $handle = fopen($this->path, 'w');
        if ($handle === false) {
            throw new RuntimeException('Unable to open file for writing: ' . $this->path);
        }

        try {
            foreach ($chunks as $chunk) {
                if (fwrite($handle, $chunk) === false) {
                    throw new RuntimeException('Unable to write to file: ' . $this->path);
                }
            }
        } finally {
            fclose($handle);
        }

        return $this;
    }

    /**
     * Calculate hash of file contents
     * @param string $algorithm Hash algorithm (md5, sha256, etc.)
     * @throws RuntimeException
     */
    public function hash(string $algorithm = 'sha256'): Str
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $hash = hash_file($algorithm, $this->path);
        if ($hash === false) {
            throw new RuntimeException('Unable to calculate hash for file: ' . $this->path);
        }

        return new Str($hash);
    }

    /**
     * Get MIME type with improved detection using finfo
     * @throws RuntimeException
     */
    public function mimeTypeDetailed(): Str
    {
        if (!$this->exists()) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Unable to open fileinfo');
        }

        try {
            $mimeType = finfo_file($finfo, $this->path);
            if ($mimeType === false) {
                throw new RuntimeException('Unable to determine MIME type: ' . $this->path);
            }

            return new Str($mimeType);
        } finally {
            finfo_close($finfo);
        }
    }

    /**
     * Create a temporary file in the system temp directory
     * @param string $prefix Prefix for the temp file name
     * @throws RuntimeException
     */
    public static function createTemp(string $prefix = 'php_'): self
    {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary file');
        }

        return new self($tempFile);
    }

    /**
     * Check if this is a temporary file (in system temp directory)
     */
    public function isTemp(): bool
    {
        $tempDir = sys_get_temp_dir();
        return str_starts_with($this->path, $tempDir);
    }
}
