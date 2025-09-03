<?php

declare(strict_types=1);

namespace PrettyPhp;

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
     * @throws RuntimeException
     */
    public function write(string $content): self
    {
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
}
