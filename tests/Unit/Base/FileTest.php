<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\File;
use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;

describe('File', function (): void {
    beforeEach(function (): void {
        $this->testDir = sys_get_temp_dir() . '/pretty_php_tests';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }

        $this->testFile = $this->testDir . '/test.txt';
        $this->nonExistentFile = $this->testDir . '/non_existent.txt';
    });

    afterEach(function (): void {
        // Clean up test files
        $files = glob($this->testDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    });

    it('can get file path', function (): void {
        $file = new File('/path/to/file.txt');
        expect($file->getPath())->toBe('/path/to/file.txt');
    });

    it('can check if file exists', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->exists())->toBeTrue();
        expect(new File($this->nonExistentFile)->exists())->toBeFalse();
    });

    it('can check if is file', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->isFile())->toBeTrue();
        expect(new File($this->testDir)->isFile())->toBeFalse();
        expect(new File($this->nonExistentFile)->isFile())->toBeFalse();
    });

    it('can check if is directory', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->isDirectory())->toBeFalse();
        expect(new File($this->testDir)->isDirectory())->toBeTrue();
        expect(new File($this->nonExistentFile)->isDirectory())->toBeFalse();
    });

    it('can check if readable', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->isReadable())->toBeTrue();
        expect(new File($this->nonExistentFile)->isReadable())->toBeFalse();
    });

    it('can check if writable', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->isWritable())->toBeTrue();
        expect(new File($this->nonExistentFile)->isWritable())->toBeFalse();
    });

    it('can get file size', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->size())->toBe(12);
    });

    it('throws exception for size of non-existent file', function (): void {
        expect(fn(): int => new File($this->nonExistentFile)->size())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can get last modified time', function (): void {
        file_put_contents($this->testFile, 'test content');
        $time = time();

        $lastModified = new File($this->testFile)->lastModified();
        expect($lastModified)->toBeInt();
        expect($lastModified)->toBeGreaterThanOrEqual($time - 5);
        expect($lastModified)->toBeLessThanOrEqual($time + 5);
    });

    it('throws exception for last modified of non-existent file', function (): void {
        expect(fn(): int => new File($this->nonExistentFile)->lastModified())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can read file content', function (): void {
        file_put_contents($this->testFile, 'test content');

        $str = new File($this->testFile)->read();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('test content');
    });

    it('throws exception when reading non-existent file', function (): void {
        expect(fn(): \PrettyPhp\Base\Str => new File($this->nonExistentFile)->read())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can read file lines', function (): void {
        file_put_contents($this->testFile, "line1\nline2\nline3");

        $arr = new File($this->testFile)->readLines();
        expect($arr)->toBeInstanceOf(Arr::class);
        expect($arr->get())->toBe(['line1', 'line2', 'line3']);
    });

    it('can read file lines using generator', function (): void {
        file_put_contents($this->testFile, "line1\nline2\nline3");

        $generator = new File($this->testFile)->readLinesGenerator();
        expect($generator)->toBeInstanceOf(\Generator::class);

        $lines = iterator_to_array($generator);
        expect($lines)->toBe(['line1', 'line2', 'line3']);
    });

    it('can read large file using generator without loading all into memory', function (): void {
        // Create a file with many lines
        $lines = [];
        for ($i = 0; $i < 1000; $i++) {
            $lines[] = 'Line number ' . $i;
        }

        file_put_contents($this->testFile, implode("\n", $lines));

        $generator = new File($this->testFile)->readLinesGenerator();
        $count = 0;
        $firstLine = null;
        $lastLine = null;

        foreach ($generator as $line) {
            if ($count === 0) {
                $firstLine = $line;
            }

            $lastLine = $line;
            $count++;
        }

        expect($count)->toBe(1000);
        expect($firstLine)->toBe('Line number 0');
        expect($lastLine)->toBe('Line number 999');
    });

    it('can handle empty file with generator', function (): void {
        file_put_contents($this->testFile, '');

        $generator = new File($this->testFile)->readLinesGenerator();
        $lines = iterator_to_array($generator);

        expect($lines)->toBe([]);
    });

    it('can handle file with single line and no newline with generator', function (): void {
        file_put_contents($this->testFile, 'single line');

        $generator = new File($this->testFile)->readLinesGenerator();
        $lines = iterator_to_array($generator);

        expect($lines)->toBe(['single line']);
    });

    it('can handle file with Windows line endings with generator', function (): void {
        file_put_contents($this->testFile, "line1\r\nline2\r\nline3");

        $generator = new File($this->testFile)->readLinesGenerator();
        $lines = iterator_to_array($generator);

        expect($lines)->toBe(['line1', 'line2', 'line3']);
    });

    it('can handle file with mixed line endings with generator', function (): void {
        file_put_contents($this->testFile, "line1\nline2\r\nline3");

        $generator = new File($this->testFile)->readLinesGenerator();
        $lines = iterator_to_array($generator);

        expect($lines)->toBe(['line1', 'line2', 'line3']);
    });

    it('throws exception when reading non-existent file with generator', function (): void {
        expect(function (): void {
            $generator = new File($this->nonExistentFile)->readLinesGenerator();
            // Force generator execution
            iterator_to_array($generator);
        })->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('closes file handle even when generator is not fully consumed', function (): void {
        file_put_contents($this->testFile, "line1\nline2\nline3\nline4\nline5");

        $generator = new File($this->testFile)->readLinesGenerator();

        // Only consume first 2 lines
        $lines = [];
        $count = 0;
        foreach ($generator as $line) {
            $lines[] = $line;
            $count++;
            if ($count === 2) {
                break;
            }
        }

        expect($lines)->toBe(['line1', 'line2']);

        // File should be accessible again (handle was closed)
        $content = file_get_contents($this->testFile);
        expect($content)->toBe("line1\nline2\nline3\nline4\nline5");
    });

    it('can write file content', function (): void {
        $file = new File($this->testFile);
        $result = $file->write('new content');

        expect($result)->toBe($file); // fluent interface
        expect(file_get_contents($this->testFile))->toBe('new content');
    });

    it('can append file content', function (): void {
        file_put_contents($this->testFile, 'initial');

        $file = new File($this->testFile);
        $result = $file->append(' appended');

        expect($result)->toBe($file); // fluent interface
        expect(file_get_contents($this->testFile))->toBe('initial appended');
    });

    it('can delete file', function (): void {
        file_put_contents($this->testFile, 'test content');

        expect(new File($this->testFile)->delete())->toBeTrue();
        expect(file_exists($this->testFile))->toBeFalse();
    });

    it('returns true when deleting non-existent file', function (): void {
        expect(new File($this->nonExistentFile)->delete())->toBeTrue();
    });

    it('can delete directory', function (): void {
        $emptyDir = $this->testDir . '/empty';
        mkdir($emptyDir);

        expect(new File($emptyDir)->delete())->toBeTrue();
        expect(is_dir($emptyDir))->toBeFalse();
    });

    it('can copy file', function (): void {
        file_put_contents($this->testFile, 'test content');
        $destination = $this->testDir . '/copied.txt';

        $file = new File($this->testFile)->copy($destination);

        expect($file)->toBeInstanceOf(File::class);
        expect($file->getPath())->toBe($destination);
        expect(file_get_contents($destination))->toBe('test content');
        expect(file_exists($this->testFile))->toBeTrue(); // original still exists
    });

    it('throws exception when copying non-existent file', function (): void {
        expect(fn(): \PrettyPhp\Base\File => new File($this->nonExistentFile)->copy($this->testDir . '/copy.txt'))
            ->toThrow(RuntimeException::class, 'Source file does not exist');
    });

    it('can move file', function (): void {
        file_put_contents($this->testFile, 'test content');
        $destination = $this->testDir . '/moved.txt';

        $file = new File($this->testFile)->move($destination);

        expect($file)->toBeInstanceOf(File::class);
        expect($file->getPath())->toBe($destination);
        expect(file_get_contents($destination))->toBe('test content');
        expect(file_exists($this->testFile))->toBeFalse(); // original moved
    });

    it('throws exception when moving non-existent file', function (): void {
        expect(fn(): \PrettyPhp\Base\File => new File($this->nonExistentFile)->move($this->testDir . '/moved.txt'))
            ->toThrow(RuntimeException::class, 'Source file does not exist');
    });

    it('can get file extension', function (): void {
        $str = new File('/path/to/file.txt')->extension();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('txt');

        expect(new File('/path/to/file')->extension()->get())->toBe('');
    });

    it('can get basename', function (): void {
        $str = new File('/path/to/file.txt')->basename();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('file.txt');
    });

    it('can get dirname', function (): void {
        $path = new File('/path/to/file.txt')->dirname();
        expect($path)->toBeInstanceOf(Path::class);
        expect($path->get())->toBe('/path/to');
    });

    it('can get mime type', function (): void {
        file_put_contents($this->testFile, 'test content');

        $str = new File($this->testFile)->mimeType();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toContain('text');
    });

    it('throws exception for mime type of non-existent file', function (): void {
        expect(fn(): \PrettyPhp\Base\Str => new File($this->nonExistentFile)->mimeType())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can get file permissions', function (): void {
        file_put_contents($this->testFile, 'test content');

        $perms = new File($this->testFile)->permissions();
        expect($perms)->toBeInt();
        expect($perms)->toBeGreaterThan(0);
    });

    it('throws exception for permissions of non-existent file', function (): void {
        expect(fn(): int => new File($this->nonExistentFile)->permissions())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can change file permissions', function (): void {
        file_put_contents($this->testFile, 'test content');

        $file = new File($this->testFile);
        $result = $file->chmod(0644);

        expect($result)->toBe($file); // fluent interface
        expect($file->permissions() & 0777)->toBe(0644);
    });

    it('throws exception for chmod on non-existent file', function (): void {
        expect(fn(): \PrettyPhp\Base\File => new File($this->nonExistentFile)->chmod(0644))
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can touch file', function (): void {
        $file = new File($this->testFile);
        $time = time();

        $result = $file->touch($time);

        expect($result)->toBe($file); // fluent interface
        expect(file_exists($this->testFile))->toBeTrue();
        expect($file->lastModified())->toBe($time);
    });

    it('can touch file without specified time', function (): void {
        $file = new File($this->testFile);
        $beforeTime = time();

        $file->touch();

        expect(file_exists($this->testFile))->toBeTrue();
        expect($file->lastModified())->toBeGreaterThanOrEqual($beforeTime);
    });

    it('throws exception when writing fails', function (): void {
        $invalidFile = '/nonexistent/directory/file.txt';
        expect(fn(): \PrettyPhp\Base\File => new File($invalidFile)->write('content'))
            ->toThrow(RuntimeException::class, 'Directory does not exist');
    });

    it('throws exception when appending fails', function (): void {
        $invalidFile = '/nonexistent/directory/file.txt';
        expect(fn(): \PrettyPhp\Base\File => new File($invalidFile)->append('content'))
            ->toThrow(RuntimeException::class, 'Directory does not exist');
    });

    it('throws exception when copy fails', function (): void {
        file_put_contents($this->testFile, 'test');
        $invalidDestination = '/nonexistent/directory/copy.txt';
        expect(fn(): \PrettyPhp\Base\File => new File($this->testFile)->copy($invalidDestination))
            ->toThrow(RuntimeException::class, 'Destination directory does not exist');
    });

    it('throws exception when move fails', function (): void {
        file_put_contents($this->testFile, 'test');
        $invalidDestination = '/nonexistent/directory/moved.txt';
        expect(fn(): \PrettyPhp\Base\File => new File($this->testFile)->move($invalidDestination))
            ->toThrow(RuntimeException::class, 'Destination directory does not exist');
    });

    it('throws exception when mime type detection fails', function (): void {
        // Create a file that mime_content_type might fail on
        file_put_contents($this->testFile, pack('H*', 'DEADBEEF')); // invalid binary data

        // Note: This test might be environment dependent
        // Some systems might still return a mime type
        try {
            $result = new File($this->testFile)->mimeType();
            expect($result)->toBeInstanceOf(Str::class);
        } catch (RuntimeException $runtimeException) {
            expect($runtimeException->getMessage())->toContain('Unable to determine MIME type');
        }
    });

    // Enhanced Methods Tests

    it('can write file atomically', function (): void {
        $file = new File($this->testFile);
        $file->writeAtomic('atomic content');
        expect($file->read()->get())->toBe('atomic content');
    });

    it('can lock file and execute callback', function (): void {
        file_put_contents($this->testFile, 'locked content');
        $file = new File($this->testFile);

        $result = $file->withLock(function ($handle) {
            $content = fread($handle, 1024);
            return $content;
        }, false);

        expect($result)->toBe('locked content');
    });

    it('can read file as stream', function (): void {
        file_put_contents($this->testFile, 'chunk1chunk2chunk3');
        $file = new File($this->testFile);

        $chunks = [];
        foreach ($file->readStream(6) as $chunk) {
            $chunks[] = $chunk;
        }

        expect($chunks)->toBe(['chunk1', 'chunk2', 'chunk3']);
    });

    it('throws exception for invalid chunk size in readStream', function (): void {
        file_put_contents($this->testFile, 'test');
        $file = new File($this->testFile);

        expect(fn() => iterator_to_array($file->readStream(0)))
            ->toThrow(\InvalidArgumentException::class, 'Chunk size must be at least 1');
    });

    it('can write file using stream', function (): void {
        $file = new File($this->testFile);
        $chunks = ['chunk1', 'chunk2', 'chunk3'];
        $file->writeStream($chunks);

        expect($file->read()->get())->toBe('chunk1chunk2chunk3');
    });

    it('can calculate file hash', function (): void {
        file_put_contents($this->testFile, 'test content');
        $file = new File($this->testFile);

        $hash = $file->hash('md5');
        expect($hash)->toBeInstanceOf(Str::class);
        expect($hash->get())->toBe(md5('test content'));

        $sha256 = $file->hash('sha256');
        expect($sha256->get())->toBe(hash('sha256', 'test content'));
    });

    it('can get detailed mime type', function (): void {
        file_put_contents($this->testFile, 'test content');
        $file = new File($this->testFile);

        $mimeType = $file->mimeTypeDetailed();
        expect($mimeType)->toBeInstanceOf(Str::class);
        expect($mimeType->get())->toContain('text');
    });

    it('can create temporary file', function (): void {
        $tempFile = File::createTemp('test_');
        expect($tempFile->exists())->toBeTrue();
        expect($tempFile->isTemp())->toBeTrue();

        // Cleanup
        $tempFile->delete();
    });

    it('can check if file is temporary', function (): void {
        $tempFile = File::createTemp();
        expect($tempFile->isTemp())->toBeTrue();

        // Note: Files in sys_get_temp_dir() are considered temp files
        // Create a file in current directory instead
        $currentDirFile = __DIR__ . '/regular_file.txt';
        file_put_contents($currentDirFile, 'test');
        $regularFile = new File($currentDirFile);
        expect($regularFile->isTemp())->toBeFalse();

        // Cleanup
        $tempFile->delete();
        unlink($currentDirFile);
    });
});
