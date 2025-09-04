<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Base\File;

#[BeforeMethods('setUp')]
#[AfterMethods('tearDown')]
class FileBench
{
    private string $testDir;

    private string $testFile;

    private string $testContent;

    private File $file;

    public function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/benchmark_files';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }

        $this->testFile = $this->testDir . '/test.txt';
        $this->testContent = str_repeat('Hello World! ', 1000); // ~12KB content
        $this->file = new File($this->testFile);
    }

    public function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }

        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchFileWrite(): void
    {
        $this->file->write($this->testContent);
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeWrite(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchFileRead(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        $this->file->read();
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeRead(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        file_get_contents($this->testFile);
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchFileExists(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        $this->file->exists();
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeExists(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        file_exists($this->testFile);
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchFileSize(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        $this->file->size();
        unlink($this->testFile); // Clean up for next iteration
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchNativeSize(): void
    {
        file_put_contents($this->testFile, $this->testContent);
        filesize($this->testFile);
        unlink($this->testFile); // Clean up for next iteration
    }
}
