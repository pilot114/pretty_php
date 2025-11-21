<?php

use PrettyPhp\Base\Arr;
use PrettyPhp\Base\File;
use PrettyPhp\Base\Path;
use PrettyPhp\Base\Str;

describe('Path', function (): void {
    beforeEach(function (): void {
        $this->testDir = sys_get_temp_dir() . '/pretty_php_path_tests';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    });

    afterEach(function (): void {
        // Clean up test files and directories recursively
        if (is_dir($this->testDir)) {
            removeDirectory($this->testDir);
        }
    });

    it('can be constructed and get value', function (): void {
        $path = new Path('/path/to/file');
        expect($path->get())->toBe('/path/to/file');
    });

    it('implements Stringable', function (): void {
        $path = new Path('/path/to/file');
        expect((string) $path)->toBe('/path/to/file');
    });

    it('can check if path exists', function (): void {
        $existingPath = new Path($this->testDir);
        $nonExistentPath = new Path($this->testDir . '/non_existent');

        expect($existingPath->exists())->toBeTrue();
        expect($nonExistentPath->exists())->toBeFalse();
    });

    it('can check if path is absolute', function (): void {
        expect(new Path('/absolute/path')->isAbsolute())->toBeTrue();
        expect(new Path('C:\\Windows')->isAbsolute())->toBeTrue();
        expect(new Path('relative/path')->isAbsolute())->toBeFalse();
        expect(new Path('')->isAbsolute())->toBeFalse();
    });

    it('can check if path is relative', function (): void {
        expect(new Path('/absolute/path')->isRelative())->toBeFalse();
        expect(new Path('relative/path')->isRelative())->toBeTrue();
        expect(new Path('')->isRelative())->toBeTrue();
    });

    it('can join path segments', function (): void {
        $path = new Path('/base')->join('sub', 'file.txt');
        expect($path->get())->toBe('/base' . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'file.txt');
    });

    it('can normalize path', function (): void {
        $realPath = realpath($this->testDir);
        $path = new Path($this->testDir)->normalize();
        expect($path->get())->toBe($realPath);
    });

    it('returns original path when normalization fails', function (): void {
        $path = new Path('/non/existent/path')->normalize();
        expect($path->get())->toBe('/non/existent/path');
    });

    it('can resolve absolute path', function (): void {
        $path = new Path('/absolute/path')->resolve();
        expect($path->get())->toBe('/absolute/path');
    });

    it('can resolve relative path', function (): void {
        $cwd = getcwd();
        $path = new Path('relative/path')->resolve();
        expect($path->get())->toContain($cwd);
    });

    it('can get relative path between two paths', function (): void {
        $from = new Path('/home/user');
        $path = $from->relative('/home/user/docs');
        expect($path->get())->toBe('docs');
    });

    it('can get relative path with parent directories', function (): void {
        $from = new Path('/home/user/project');
        $path = $from->relative('/home/user/docs');
        expect($path->get())->toBe('..' . DIRECTORY_SEPARATOR . 'docs');
    });

    it('can get parent directory', function (): void {
        $path = new Path('/path/to/file.txt')->parent();
        expect($path->get())->toBe('/path/to');
    });

    it('can get basename', function (): void {
        $str = new Path('/path/to/file.txt')->basename();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('file.txt');
    });

    it('can get extension', function (): void {
        $str = new Path('/path/to/file.txt')->extension();
        expect($str)->toBeInstanceOf(Str::class);
        expect($str->get())->toBe('txt');

        expect(new Path('/path/to/file')->extension()->get())->toBe('');
    });

    it('can remove extension', function (): void {
        $path = new Path('/path/to/file.txt')->withoutExtension();
        expect($path->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file');
    });

    it('can change extension', function (): void {
        $path = new Path('/path/to/file.txt')->withExtension('md');
        expect($path->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file.md');
    });

    it('can change extension with leading dot', function (): void {
        $path = new Path('/path/to/file.txt')->withExtension('.md');
        expect($path->get())->toBe('/path/to' . DIRECTORY_SEPARATOR . 'file.md');
    });

    it('can concat suffix', function (): void {
        $path = new Path('/path/to/file')->concat('.txt');
        expect($path->get())->toBe('/path/to/file.txt');
    });

    it('can prepend prefix', function (): void {
        $path = new Path('path/to/file')->prepend('/base/');
        expect($path->get())->toBe('/base/path/to/file');
    });

    it('can create directory', function (): void {
        $newDir = $this->testDir . '/new_directory';
        $path = new Path($newDir);

        $result = $path->mkdir();

        expect($result)->toBe($path); // fluent interface
        expect(is_dir($newDir))->toBeTrue();
    });

    it('can create directory recursively', function (): void {
        $nestedDir = $this->testDir . '/level1/level2/level3';
        $path = new Path($nestedDir);

        $path->mkdir(0755, true);

        expect(is_dir($nestedDir))->toBeTrue();
    });

    it('can list files in directory', function (): void {
        // Create test files
        touch($this->testDir . '/file1.txt');
        touch($this->testDir . '/file2.txt');
        mkdir($this->testDir . '/subdir');

        $arr = new Path($this->testDir)->listFiles();

        expect($arr)->toBeInstanceOf(Arr::class);
        $files = $arr->get();
        expect($files)->toContain('file1.txt');
        expect($files)->toContain('file2.txt');
        expect($files)->toContain('subdir');
        expect($files)->not->toContain('.');
        expect($files)->not->toContain('..');
    });

    it('throws exception when listing non-directory', function (): void {
        $filePath = $this->testDir . '/file.txt';
        touch($filePath);

        expect(fn(): \PrettyPhp\Base\Arr => new Path($filePath)->listFiles())
            ->toThrow(RuntimeException::class, 'Path is not a directory');
    });

    it('can glob files', function (): void {
        // Create test files
        touch($this->testDir . '/test1.txt');
        touch($this->testDir . '/test2.txt');
        touch($this->testDir . '/other.log');

        $arr = new Path($this->testDir)->glob('*.txt');

        expect($arr)->toBeInstanceOf(Arr::class);
        $files = $arr->get();
        expect($files)->toHaveCount(2);
        expect($files)->toContain($this->testDir . '/test1.txt');
        expect($files)->toContain($this->testDir . '/test2.txt');
    });

    it('returns empty array for no glob matches', function (): void {
        $arr = new Path($this->testDir)->glob('*.nonexistent');
        expect($arr->get())->toBe([]);
    });

    it('can check if path is file', function (): void {
        $filePath = $this->testDir . '/test.txt';
        touch($filePath);

        expect(new Path($filePath)->isFile())->toBeTrue();
        expect(new Path($this->testDir)->isFile())->toBeFalse();
    });

    it('can check if path is directory', function (): void {
        $filePath = $this->testDir . '/test.txt';
        touch($filePath);

        expect(new Path($this->testDir)->isDirectory())->toBeTrue();
        expect(new Path($filePath)->isDirectory())->toBeFalse();
    });

    it('can get file size', function (): void {
        $filePath = $this->testDir . '/test.txt';
        file_put_contents($filePath, 'test content');

        $size = new Path($filePath)->size();
        expect($size)->toBe(12);
    });

    it('can get directory size', function (): void {
        // Create files in directory
        file_put_contents($this->testDir . '/file1.txt', 'content1');
        file_put_contents($this->testDir . '/file2.txt', 'content2');

        $size = new Path($this->testDir)->size();
        expect($size)->toBeGreaterThan(0);
    });

    it('throws exception for size of non-existent path', function (): void {
        expect(fn(): int => new Path($this->testDir . '/nonexistent')->size())
            ->toThrow(RuntimeException::class, 'Path does not exist');
    });

    it('can convert to File', function (): void {
        $file = new Path('/path/to/file.txt')->toFile();
        expect($file)->toBeInstanceOf(File::class);
        expect($file->getPath())->toBe('/path/to/file.txt');
    });

    it('handles root directory edge cases', function (): void {
        $result = new Path('/')->parent();
        expect($result->get())->toBe('/');

        $result = new Path('.')->basename();
        expect($result->get())->toBe('.');
    });

    it('handles file without extension in withoutExtension', function (): void {
        $path = new Path('/path/to/file')->withoutExtension();
        expect($path->get())->toBe('/path/to/file');
    });

    it('handles empty filename in withoutExtension', function (): void {
        $path = new Path('/path/to/')->withoutExtension();
        expect($path->get())->toBe('/path/to');
    });

    // Enhanced Methods Tests

    it('can normalize path separators', function (): void {
        $path = new Path('path/to\\file');
        $normalized = $path->normalizePathSeparators();
        expect($normalized->get())->toContain(DIRECTORY_SEPARATOR);
    });

    it('can detect Windows paths', function (): void {
        expect(new Path('C:\\Windows\\System32')->isWindowsPath())->toBeTrue();
        expect(new Path('D:/path/to/file')->isWindowsPath())->toBeTrue();
        expect(new Path('/usr/local/bin')->isWindowsPath())->toBeFalse();
        expect(new Path('relative/path')->isWindowsPath())->toBeFalse();
    });

    it('can validate safe paths', function (): void {
        expect(new Path('/safe/path')->isSafe())->toBeTrue();
        expect(new Path('../etc/passwd')->isSafe())->toBeFalse();
        expect(new Path("/path\0null")->isSafe())->toBeFalse();
    });

    it('can validate path format', function (): void {
        expect(new Path('/valid/path')->isValid())->toBeTrue();
        expect(new Path('')->isValid())->toBeFalse();
        expect(new Path("/null\0byte")->isValid())->toBeFalse();
    });

    it('can convert to URL path', function (): void {
        $path = new Path('path\\to\\file');
        $urlPath = $path->toUrlPath();
        expect($urlPath->get())->toBe('path/to/file');
    });

    it('can create from URL path', function (): void {
        $path = Path::fromUrlPath('path/to/file');
        expect($path->get())->toContain('path');
        expect($path->get())->toContain('file');
    });

    it('can URL encode path', function (): void {
        $path = new Path('path/to/my file.txt');
        $encoded = $path->urlEncode();
        expect($encoded->get())->toBe('path/to/my%20file.txt');
    });

    it('can check if paths are the same', function (): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $path1 = new Path($tempFile);
        $path2 = new Path($tempFile);
        expect($path1->isSameAs($path2))->toBeTrue();

        unlink($tempFile);
    });

    it('can get real path', function (): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $path = new Path($tempFile);
        $realPath = $path->realPath();
        expect($realPath->exists())->toBeTrue();

        unlink($tempFile);
    });

    it('throws exception for invalid real path', function (): void {
        $path = new Path('/nonexistent/path');
        expect(fn() => $path->realPath())
            ->toThrow(RuntimeException::class, 'Unable to resolve real path');
    });

    it('can perform recursive glob', function (): void {
        $tempDir = sys_get_temp_dir() . '/test_glob_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/subdir');
        file_put_contents($tempDir . '/file1.txt', 'test');
        file_put_contents($tempDir . '/subdir/file2.txt', 'test');

        $path = new Path($tempDir);
        $results = $path->globRecursive('*.txt');

        expect($results->count())->toBeGreaterThan(0);

        // Cleanup
        unlink($tempDir . '/file1.txt');
        unlink($tempDir . '/subdir/file2.txt');
        rmdir($tempDir . '/subdir');
        rmdir($tempDir);
    });
});
