<?php

use PrettyPhp\Path;
use PrettyPhp\File;
use PrettyPhp\Str;
use PrettyPhp\Arr;

describe('Path', function () {
    beforeEach(function () {
        $this->testDir = sys_get_temp_dir() . '/pretty_php_path_tests';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    });

    afterEach(function () {
        // Clean up test files and directories recursively
        if (is_dir($this->testDir)) {
            removeDirectory($this->testDir);
        }
    });

    it('can be constructed and get value', function () {
        $path = new Path('/path/to/file');
        expect($path->get())->toBe('/path/to/file');
    });

    it('implements Stringable', function () {
        $path = new Path('/path/to/file');
        expect((string) $path)->toBe('/path/to/file');
    });

    it('can check if path exists', function () {
        $existingPath = new Path($this->testDir);
        $nonExistentPath = new Path($this->testDir . '/non_existent');
        
        expect($existingPath->exists())->toBeTrue();
        expect($nonExistentPath->exists())->toBeFalse();
    });

    it('can check if path is absolute', function () {
        expect((new Path('/absolute/path'))->isAbsolute())->toBeTrue();
        expect((new Path('C:\\Windows'))->isAbsolute())->toBeTrue();
        expect((new Path('relative/path'))->isAbsolute())->toBeFalse();
        expect((new Path(''))->isAbsolute())->toBeFalse();
    });

    it('can check if path is relative', function () {
        expect((new Path('/absolute/path'))->isRelative())->toBeFalse();
        expect((new Path('relative/path'))->isRelative())->toBeTrue();
        expect((new Path(''))->isRelative())->toBeTrue();
    });

    it('can join path segments', function () {
        $result = (new Path('/base'))->join('sub', 'file.txt');
        expect($result->get())->toBe('/base' . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'file.txt');
    });

    it('can normalize path', function () {
        $realPath = realpath($this->testDir);
        $result = (new Path($this->testDir))->normalize();
        expect($result->get())->toBe($realPath);
    });

    it('returns original path when normalization fails', function () {
        $result = (new Path('/non/existent/path'))->normalize();
        expect($result->get())->toBe('/non/existent/path');
    });

    it('can resolve absolute path', function () {
        $result = (new Path('/absolute/path'))->resolve();
        expect($result->get())->toBe('/absolute/path');
    });

    it('can resolve relative path', function () {
        $cwd = getcwd();
        $result = (new Path('relative/path'))->resolve();
        expect($result->get())->toContain($cwd);
    });

    it('can get relative path between two paths', function () {
        $from = new Path('/home/user');
        $result = $from->relative('/home/user/docs');
        expect($result->get())->toBe('docs');
    });

    it('can get relative path with parent directories', function () {
        $from = new Path('/home/user/project');
        $result = $from->relative('/home/user/docs');
        expect($result->get())->toBe('..' . DIRECTORY_SEPARATOR . 'docs');
    });

    it('can get parent directory', function () {
        $result = (new Path('/path/to/file.txt'))->parent();
        expect($result->get())->toBe('/path/to');
    });

    it('can get basename', function () {
        $result = (new Path('/path/to/file.txt'))->basename();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('file.txt');
    });

    it('can get extension', function () {
        $result = (new Path('/path/to/file.txt'))->extension();
        expect($result)->toBeInstanceOf(Str::class);
        expect($result->get())->toBe('txt');
        
        expect((new Path('/path/to/file'))->extension()->get())->toBe('');
    });

    it('can remove extension', function () {
        $result = (new Path('/path/to/file.txt'))->withoutExtension();
        expect($result->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file');
    });

    it('can change extension', function () {
        $result = (new Path('/path/to/file.txt'))->withExtension('md');
        expect($result->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file.md');
    });

    it('can change extension with leading dot', function () {
        $result = (new Path('/path/to/file.txt'))->withExtension('.md');
        expect($result->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file.md');
    });

    it('can concat suffix', function () {
        $result = (new Path('/path/to/file'))->concat('.txt');
        expect($result->get())->toBe('/path/to/file.txt');
    });

    it('can prepend prefix', function () {
        $result = (new Path('path/to/file'))->prepend('/base/');
        expect($result->get())->toBe('/base/path/to/file');
    });

    it('can create directory', function () {
        $newDir = $this->testDir . '/new_directory';
        $path = new Path($newDir);
        
        $result = $path->mkdir();
        
        expect($result)->toBe($path); // fluent interface
        expect(is_dir($newDir))->toBeTrue();
    });

    it('can create directory recursively', function () {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        $path = new Path($nestedDir);
        
        $path->mkdir(0755, true);
        
        expect(is_dir($nestedDir))->toBeTrue();
    });

    it('can list files in directory', function () {
        // Create test files
        touch($this->testDir . '/file1.txt');
        touch($this->testDir . '/file2.txt');
        mkdir($this->testDir . '/subdir');
        
        $result = (new Path($this->testDir))->listFiles();
        
        expect($result)->toBeInstanceOf(Arr::class);
        $files = $result->toArray();
        expect($files)->toContain('file1.txt');
        expect($files)->toContain('file2.txt');
        expect($files)->toContain('subdir');
        expect($files)->not->toContain('.');
        expect($files)->not->toContain('..');
    });

    it('throws exception when listing non-directory', function () {
        $filePath = $this->testDir . '/file.txt';
        touch($filePath);
        
        expect(fn() => (new Path($filePath))->listFiles())
            ->toThrow(RuntimeException::class, 'Path is not a directory');
    });

    it('can glob files', function () {
        // Create test files
        touch($this->testDir . '/test1.txt');
        touch($this->testDir . '/test2.txt');
        touch($this->testDir . '/other.log');
        
        $result = (new Path($this->testDir))->glob('*.txt');
        
        expect($result)->toBeInstanceOf(Arr::class);
        $files = $result->toArray();
        expect($files)->toHaveCount(2);
        expect($files)->toContain($this->testDir . '/test1.txt');
        expect($files)->toContain($this->testDir . '/test2.txt');
    });

    it('returns empty array for no glob matches', function () {
        $result = (new Path($this->testDir))->glob('*.nonexistent');
        expect($result->toArray())->toBe([]);
    });

    it('can check if path is file', function () {
        $filePath = $this->testDir . '/test.txt';
        touch($filePath);
        
        expect((new Path($filePath))->isFile())->toBeTrue();
        expect((new Path($this->testDir))->isFile())->toBeFalse();
    });

    it('can check if path is directory', function () {
        $filePath = $this->testDir . '/test.txt';
        touch($filePath);
        
        expect((new Path($this->testDir))->isDirectory())->toBeTrue();
        expect((new Path($filePath))->isDirectory())->toBeFalse();
    });

    it('can get file size', function () {
        $filePath = $this->testDir . '/test.txt';
        file_put_contents($filePath, 'test content');
        
        $size = (new Path($filePath))->size();
        expect($size)->toBe(12);
    });

    it('can get directory size', function () {
        // Create files in directory
        file_put_contents($this->testDir . '/file1.txt', 'content1');
        file_put_contents($this->testDir . '/file2.txt', 'content2');
        
        $size = (new Path($this->testDir))->size();
        expect($size)->toBeGreaterThan(0);
    });

    it('throws exception for size of non-existent path', function () {
        expect(fn() => (new Path($this->testDir . '/nonexistent'))->size())
            ->toThrow(RuntimeException::class, 'Path does not exist');
    });

    it('can convert to File', function () {
        $result = (new Path('/path/to/file.txt'))->toFile();
        expect($result)->toBeInstanceOf(File::class);
        expect($result->getPath())->toBe('/path/to/file.txt');
    });

    it('handles root directory edge cases', function () {
        $result = (new Path('/'))->parent();
        expect($result->get())->toBe('/');
        
        $result = (new Path('.'))->basename();
        expect($result->get())->toBe('.');
    });

    it('handles file without extension in withoutExtension', function () {
        $result = (new Path('/path/to/file'))->withoutExtension();
        expect($result->get())->toBe('/path/to/file');
    });

    it('handles empty filename in withoutExtension', function () {
        $result = (new Path('/path/to/'))->withoutExtension();
        expect($result->get())->toBe('/path/to');
    });
});