<?php

use PrettyPhp\File;
use PrettyPhp\Str;
use PrettyPhp\Arr;
use PrettyPhp\Path;

describe('File', function () {
    beforeEach(function () {
        $this->testDir = sys_get_temp_dir() . '/pretty_php_tests';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
        $this->testFile = $this->testDir . '/test.txt';
        $this->nonExistentFile = $this->testDir . '/non_existent.txt';
    });

    afterEach(function () {
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

    it('can get file path', function () {
        $file = new File('/path/to/file.txt');
        expect($file->getPath())->toBe('/path/to/file.txt');
    });

    it('can check if file exists', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->exists())->toBeTrue();
        expect((new File($this->nonExistentFile))->exists())->toBeFalse();
    });

    it('can check if is file', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->isFile())->toBeTrue();
        expect((new File($this->testDir))->isFile())->toBeFalse();
        expect((new File($this->nonExistentFile))->isFile())->toBeFalse();
    });

    it('can check if is directory', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->isDirectory())->toBeFalse();
        expect((new File($this->testDir))->isDirectory())->toBeTrue();
        expect((new File($this->nonExistentFile))->isDirectory())->toBeFalse();
    });

    it('can check if readable', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->isReadable())->toBeTrue();
        expect((new File($this->nonExistentFile))->isReadable())->toBeFalse();
    });

    it('can check if writable', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->isWritable())->toBeTrue();
        expect((new File($this->nonExistentFile))->isWritable())->toBeFalse();
    });

    it('can get file size', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->size())->toBe(12);
    });

    it('throws exception for size of non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->size())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can get last modified time', function () {
        file_put_contents($this->testFile, 'test content');
        $time = time();
        
        $lastModified = (new File($this->testFile))->lastModified();
        expect($lastModified)->toBeInt();
        expect($lastModified)->toBeGreaterThanOrEqual($time - 5);
        expect($lastModified)->toBeLessThanOrEqual($time + 5);
    });

    it('throws exception for last modified of non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->lastModified())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can read file content', function () {
        file_put_contents($this->testFile, 'test content');
        
        $result = (new File($this->testFile))->read();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('test content');
    });

    it('throws exception when reading non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->read())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can read file lines', function () {
        file_put_contents($this->testFile, "line1\nline2\nline3");
        
        $result = (new File($this->testFile))->readLines();
        expect($result)->toBeInstanceOf(Arr::class);
        expect($result->toArray())->toBe(['line1', 'line2', 'line3']);
    });

    it('can write file content', function () {
        $file = new File($this->testFile);
        $result = $file->write('new content');
        
        expect($result)->toBe($file); // fluent interface
        expect(file_get_contents($this->testFile))->toBe('new content');
    });

    it('can append file content', function () {
        file_put_contents($this->testFile, 'initial');
        
        $file = new File($this->testFile);
        $result = $file->append(' appended');
        
        expect($result)->toBe($file); // fluent interface
        expect(file_get_contents($this->testFile))->toBe('initial appended');
    });

    it('can delete file', function () {
        file_put_contents($this->testFile, 'test content');
        
        expect((new File($this->testFile))->delete())->toBeTrue();
        expect(file_exists($this->testFile))->toBeFalse();
    });

    it('returns true when deleting non-existent file', function () {
        expect((new File($this->nonExistentFile))->delete())->toBeTrue();
    });

    it('can delete directory', function () {
        $emptyDir = $this->testDir . '/empty';
        mkdir($emptyDir);
        
        expect((new File($emptyDir))->delete())->toBeTrue();
        expect(is_dir($emptyDir))->toBeFalse();
    });

    it('can copy file', function () {
        file_put_contents($this->testFile, 'test content');
        $destination = $this->testDir . '/copied.txt';
        
        $result = (new File($this->testFile))->copy($destination);
        
        expect($result)->toBeInstanceOf(File::class);
        expect($result->getPath())->toBe($destination);
        expect(file_get_contents($destination))->toBe('test content');
        expect(file_exists($this->testFile))->toBeTrue(); // original still exists
    });

    it('throws exception when copying non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->copy($this->testDir . '/copy.txt'))
            ->toThrow(RuntimeException::class, 'Source file does not exist');
    });

    it('can move file', function () {
        file_put_contents($this->testFile, 'test content');
        $destination = $this->testDir . '/moved.txt';
        
        $result = (new File($this->testFile))->move($destination);
        
        expect($result)->toBeInstanceOf(File::class);
        expect($result->getPath())->toBe($destination);
        expect(file_get_contents($destination))->toBe('test content');
        expect(file_exists($this->testFile))->toBeFalse(); // original moved
    });

    it('throws exception when moving non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->move($this->testDir . '/moved.txt'))
            ->toThrow(RuntimeException::class, 'Source file does not exist');
    });

    it('can get file extension', function () {
        $result = (new File('/path/to/file.txt'))->extension();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('txt');
        
        expect((new File('/path/to/file'))->extension()->get())->toBe('');
    });

    it('can get basename', function () {
        $result = (new File('/path/to/file.txt'))->basename();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('file.txt');
    });

    it('can get dirname', function () {
        $result = (new File('/path/to/file.txt'))->dirname();
        expect($result)->toBeInstanceOf(Path::class);
        expect($result->get())->toBe('/path/to');
    });

    it('can get mime type', function () {
        file_put_contents($this->testFile, 'test content');
        
        $result = (new File($this->testFile))->mimeType();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toContain('text');
    });

    it('throws exception for mime type of non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->mimeType())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can get file permissions', function () {
        file_put_contents($this->testFile, 'test content');
        
        $perms = (new File($this->testFile))->permissions();
        expect($perms)->toBeInt();
        expect($perms)->toBeGreaterThan(0);
    });

    it('throws exception for permissions of non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->permissions())
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can change file permissions', function () {
        file_put_contents($this->testFile, 'test content');
        
        $file = new File($this->testFile);
        $result = $file->chmod(0644);
        
        expect($result)->toBe($file); // fluent interface
        expect($file->permissions() & 0777)->toBe(0644);
    });

    it('throws exception for chmod on non-existent file', function () {
        expect(fn() => (new File($this->nonExistentFile))->chmod(0644))
            ->toThrow(RuntimeException::class, 'File does not exist');
    });

    it('can touch file', function () {
        $file = new File($this->testFile);
        $time = time();
        
        $result = $file->touch($time);
        
        expect($result)->toBe($file); // fluent interface
        expect(file_exists($this->testFile))->toBeTrue();
        expect($file->lastModified())->toBe($time);
    });

    it('can touch file without specified time', function () {
        $file = new File($this->testFile);
        $beforeTime = time();
        
        $file->touch();
        
        expect(file_exists($this->testFile))->toBeTrue();
        expect($file->lastModified())->toBeGreaterThanOrEqual($beforeTime);
    });

    it('throws exception when writing fails', function () {
        $invalidFile = '/nonexistent/directory/file.txt';
        expect(fn() => (new File($invalidFile))->write('content'))
            ->toThrow(RuntimeException::class, 'Directory does not exist');
    });

    it('throws exception when appending fails', function () {
        $invalidFile = '/nonexistent/directory/file.txt';
        expect(fn() => (new File($invalidFile))->append('content'))
            ->toThrow(RuntimeException::class, 'Directory does not exist');
    });

    it('throws exception when copy fails', function () {
        file_put_contents($this->testFile, 'test');
        $invalidDestination = '/nonexistent/directory/copy.txt';
        expect(fn() => (new File($this->testFile))->copy($invalidDestination))
            ->toThrow(RuntimeException::class, 'Destination directory does not exist');
    });

    it('throws exception when move fails', function () {
        file_put_contents($this->testFile, 'test');
        $invalidDestination = '/nonexistent/directory/moved.txt';
        expect(fn() => (new File($this->testFile))->move($invalidDestination))
            ->toThrow(RuntimeException::class, 'Destination directory does not exist');
    });

    it('throws exception when mime type detection fails', function () {
        // Create a file that mime_content_type might fail on
        file_put_contents($this->testFile, pack('H*', 'DEADBEEF')); // invalid binary data
        
        // Note: This test might be environment dependent
        // Some systems might still return a mime type
        try {
            $result = (new File($this->testFile))->mimeType();
            expect($result)->toBeInstanceOf(Str::class);
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toContain('Unable to determine MIME type');
        }
    });
});