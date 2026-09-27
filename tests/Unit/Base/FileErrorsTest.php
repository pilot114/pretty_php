<?php

declare(strict_types=1);

use PrettyPhp\Base\File;
use PrettyPhp\Exception\FileException;

mutates(File::class);

describe('File error messages', function (): void {
    beforeEach(function (): void {
        $this->dir = sys_get_temp_dir() . '/pretty_php_file_errors_' . uniqid();
        mkdir($this->dir);
        $this->missing = $this->dir . '/missing.txt';
        $this->missingDir = $this->dir . '/no-dir';
    });

    afterEach(function (): void {
        removeDirectory($this->dir);
    });

    it('names the missing file', function (string $method, array $args): void {
        $file = new File($this->missing);
        expect(fn (): mixed => is_a($result = $file->{$method}(...$args), \Generator::class) ? iterator_to_array($result) : $result)
            ->toThrow(FileException::class, 'File does not exist: ' . $this->missing);
    })->with([
        'size' => ['size', []],
        'lastModified' => ['lastModified', []],
        'read' => ['read', []],
        'readLinesGenerator' => ['readLinesGenerator', []],
        'mimeType' => ['mimeType', []],
        'permissions' => ['permissions', []],
        'chmod' => ['chmod', [0644]],
        'withLock' => ['withLock', [fn ($handle): int => 1]],
        'readStream' => ['readStream', []],
        'hash' => ['hash', []],
        'mimeTypeDetailed' => ['mimeTypeDetailed', []],
    ]);

    it('names the missing directory', function (string $method, array $args): void {
        $file = new File($this->missingDir . '/file.txt');
        expect(fn (): mixed => $file->{$method}(...$args))
            ->toThrow(FileException::class, 'Directory does not exist: ' . $this->missingDir);
    })->with([
        'write' => ['write', ['x']],
        'append' => ['append', ['x']],
        'writeAtomic' => ['writeAtomic', ['x']],
        'writeStream' => ['writeStream', [['x']]],
    ]);

    it('names source and destination on copy and move', function (): void {
        $missing = new File($this->missing);
        expect(fn (): File => $missing->copy($this->dir . '/copy.txt'))
            ->toThrow(FileException::class, 'Source file does not exist: ' . $this->missing);
        expect(fn (): File => $missing->move($this->dir . '/moved.txt'))
            ->toThrow(FileException::class, 'Source file does not exist: ' . $this->missing);

        $existing = new File($this->dir . '/source.txt')->write('data');
        expect(fn (): File => $existing->copy($this->missingDir . '/copy.txt'))
            ->toThrow(FileException::class, 'Destination directory does not exist: ' . $this->missingDir);
        expect(fn (): File => $existing->move($this->missingDir . '/moved.txt'))
            ->toThrow(FileException::class, 'Destination directory does not exist: ' . $this->missingDir);
    });

    it('names the file when the operating system refuses', function (): void {
        $full = new File('/dev/full');
        expect(fn (): File => @$full->write('x'))->toThrow(FileException::class, 'Unable to write file: /dev/full');
        expect(fn (): File => @$full->append('x'))->toThrow(FileException::class, 'Unable to append to file: /dev/full');
        expect(fn (): File => @$full->writeStream(['x']))->toThrow(FileException::class, 'Unable to write to file: /dev/full');

        $readonlyDir = $this->dir . '/readonly';
        mkdir($readonlyDir, 0555);
        try {
            $target = $readonlyDir . '/new.txt';
            expect(fn (): File => @new File($target)->touch())
                ->toThrow(FileException::class, 'Unable to touch file: ' . $target);
            expect(fn (): File => @new File($target)->writeStream(['x']))
                ->toThrow(FileException::class, 'Unable to open file for writing: ' . $target);

            $source = new File($this->dir . '/source.txt')->write('data');
            expect(fn (): File => @$source->copy($target))
                ->toThrow(FileException::class, sprintf('Unable to copy file from %s to %s', $source->getPath(), $target));
            expect(fn (): File => @$source->move($target))
                ->toThrow(FileException::class, sprintf('Unable to move file from %s to %s', $source->getPath(), $target));
        } finally {
            chmod($readonlyDir, 0755);
        }

        $locked = $this->dir . '/locked.txt';
        file_put_contents($locked, 'x');
        chmod($locked, 0000);
        try {
            $file = new File($locked);
            expect(fn (): mixed => @$file->read())->toThrow(FileException::class, 'Unable to read file: ' . $locked);
            expect(fn (): array => iterator_to_array(@$file->readLinesGenerator()))
                ->toThrow(FileException::class, 'Unable to open file: ' . $locked);
            expect(fn (): array => iterator_to_array(@$file->readStream()))
                ->toThrow(FileException::class, 'Unable to open file: ' . $locked);
            expect(fn (): mixed => @$file->withLock(fn ($h): int => 1))
                ->toThrow(FileException::class, 'Unable to open file: ' . $locked);
            expect(fn (): mixed => @$file->hash())
                ->toThrow(FileException::class, 'Unable to calculate hash for file: ' . $locked);
            expect(fn (): mixed => @$file->mimeType())
                ->toThrow(FileException::class, 'Unable to determine MIME type: ' . $locked);
            expect(fn (): mixed => @$file->mimeTypeDetailed())
                ->toThrow(FileException::class, 'Unable to determine MIME type: ' . $locked);
        } finally {
            chmod($locked, 0644);
        }

        expect(fn (): File => @new File('/etc/passwd')->chmod(0777))
            ->toThrow(FileException::class, 'Unable to change file permissions: /etc/passwd');
    })->skip(fn (): bool => posix_geteuid() === 0, 'root ignores file permissions');
});

describe('File locking and streaming details', function (): void {
    beforeEach(function (): void {
        $this->path = tempnam(sys_get_temp_dir(), 'pretty_php_lock_');
    });

    afterEach(function (): void {
        @unlink($this->path);
    });

    it('takes an exclusive or shared lock', function (): void {
        file_put_contents($this->path, 'data');
        $file = new File($this->path);

        $sharedPossibleWhileExclusive = $file->withLock(function (): bool {
            $other = fopen($this->path, 'r');
            $acquired = flock($other, LOCK_SH | LOCK_NB);
            fclose($other);

            return $acquired;
        });
        expect($sharedPossibleWhileExclusive)->toBeFalse();

        $sharedPossibleWhileShared = $file->withLock(function (): bool {
            $other = fopen($this->path, 'r');
            $acquired = flock($other, LOCK_SH | LOCK_NB);
            fclose($other);

            return $acquired;
        }, false);
        expect($sharedPossibleWhileShared)->toBeTrue();
    });

    it('never yields empty chunks', function (): void {
        file_put_contents($this->path, '');
        expect(iterator_to_array(new File($this->path)->readStream(2)))->toBe([]);

        file_put_contents($this->path, 'abcd');
        expect(iterator_to_array(new File($this->path)->readStream(2), false))->toBe(['ab', 'cd']);
    });
});
