<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Path;

#[BeforeMethods('setUp')]
class PathBench
{
    private array $paths;

    private array $pathObjects;

    public function setUp(): void
    {
        $this->paths = [
            '/home/user/documents/file.txt',
            '/var/www/html/index.php',
            'relative/path/to/file.js',
            '../parent/directory/script.py',
            '/usr/local/bin/composer',
            'C:\Windows\System32\notepad.exe', // Windows path
        ];

        $this->pathObjects = array_map(fn(string $path): \PrettyPhp\Path => new Path($path), $this->paths);
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchPathBasename(): void
    {
        foreach ($this->pathObjects as $pathObject) {
            $pathObject->basename();
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeBasename(): void
    {
        foreach ($this->paths as $path) {
            basename((string) $path);
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchPathParent(): void
    {
        foreach ($this->pathObjects as $pathObject) {
            $pathObject->parent();
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeDirname(): void
    {
        foreach ($this->paths as $path) {
            dirname((string) $path);
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchPathExtension(): void
    {
        foreach ($this->pathObjects as $pathObject) {
            $pathObject->extension();
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeExtension(): void
    {
        foreach ($this->paths as $path) {
            pathinfo((string) $path, PATHINFO_EXTENSION);
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchPathJoin(): void
    {
        foreach ($this->pathObjects as $pathObject) {
            $pathObject->join('subfolder', 'file.txt');
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeJoin(): void
    {
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchPathAbsolute(): void
    {
        foreach ($this->pathObjects as $pathObject) {
            $pathObject->isAbsolute();
        }
    }

    #[Revs(10000)]
    #[Iterations(10)]
    public function benchNativeAbsolute(): void
    {
        foreach ($this->paths as $path) {
            if (str_starts_with((string) $path, '/')) {
                continue;
            }

            if (strlen((string) $path) <= 1) {
                continue;
            }
        }
    }
}
