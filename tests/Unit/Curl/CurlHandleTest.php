<?php

use PrettyPhp\Curl\CurlException;
use PrettyPhp\Curl\CurlHandle;

describe('CurlHandle', function (): void {
    describe('construction', function (): void {
        it('can be created without url', function (): void {
            $handle = new CurlHandle();
            expect($handle)->toBeInstanceOf(CurlHandle::class);
            expect($handle->getHandle())->toBeInstanceOf(\CurlHandle::class);
        });

        it('can be created with url', function (): void {
            $handle = new CurlHandle('https://example.com');
            expect($handle)->toBeInstanceOf(CurlHandle::class);
        });
    });

    describe('setOption', function (): void {
        it('can set a single option', function (): void {
            $handle = new CurlHandle();
            $result = $handle->setOption(CURLOPT_RETURNTRANSFER, true);
            expect($result)->toBe($handle); // chainable
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->setOption(CURLOPT_RETURNTRANSFER, true))
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('setOptions', function (): void {
        it('can set multiple options', function (): void {
            $handle = new CurlHandle();
            $result = $handle->setOptions([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            expect($result)->toBe($handle); // chainable
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->setOptions([CURLOPT_RETURNTRANSFER => true]))
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('execute', function (): void {
        it('reads a local file with file:// protocol', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_test_');
            file_put_contents($tmpFile, 'hello curl');

            try {
                $handle = new CurlHandle('file://' . $tmpFile);
                $handle->setOption(CURLOPT_RETURNTRANSFER, true);
                $result = $handle->execute();
                expect($result)->toBe('hello curl');
            } finally {
                unlink($tmpFile);
            }
        });

        it('throws on failed request', function (): void {
            $handle = new CurlHandle('invalid://url');
            $handle->setOption(CURLOPT_RETURNTRANSFER, true);
            $handle->setOption(CURLOPT_CONNECTTIMEOUT_MS, 1);

            expect(fn () => $handle->execute())
                ->toThrow(CurlException::class);
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->execute())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('getInfo', function (): void {
        it('returns full info array when no option given', function (): void {
            $handle = new CurlHandle();
            $info = $handle->getInfo();
            expect($info)->toBeArray();
            expect($info)->toHaveKey('url');
        });

        it('returns specific info when option given', function (): void {
            $handle = new CurlHandle('https://example.com');
            $url = $handle->getInfo(CURLINFO_EFFECTIVE_URL);
            expect($url)->toBe('https://example.com');
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->getInfo())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('error methods', function (): void {
        it('returns error number 0 when no error', function (): void {
            $handle = new CurlHandle();
            expect($handle->getErrorNumber())->toBe(0);
        });

        it('returns empty error message when no error', function (): void {
            $handle = new CurlHandle();
            expect($handle->getErrorMessage())->toBe('');
        });

        it('returns error string for known error code', function (): void {
            $result = CurlHandle::getErrorString(CURLE_UNSUPPORTED_PROTOCOL);
            expect($result)->toBeString();
            expect($result)->not->toBe('');
        });

        it('throws on closed handle for getErrorNumber', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->getErrorNumber())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });

        it('throws on closed handle for getErrorMessage', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->getErrorMessage())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('escape and unescape', function (): void {
        it('escapes a string', function (): void {
            $handle = new CurlHandle();
            $result = $handle->escape('hello world');
            expect($result)->toBe('hello%20world');
        });

        it('unescapes a string', function (): void {
            $handle = new CurlHandle();
            $result = $handle->unescape('hello%20world');
            expect($result)->toBe('hello world');
        });

        it('escape and unescape are inverse operations', function (): void {
            $handle = new CurlHandle();
            $original = 'test string with spaces & special=chars';
            $escaped = $handle->escape($original);
            $unescaped = $handle->unescape($escaped);
            expect($unescaped)->toBe($original);
        });

        it('throws on closed handle for escape', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->escape('test'))
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });

        it('throws on closed handle for unescape', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->unescape('test'))
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('copy', function (): void {
        it('creates a copy of the handle', function (): void {
            $handle = new CurlHandle('https://example.com');
            $handle->setOption(CURLOPT_RETURNTRANSFER, true);
            $copy = $handle->copy();

            expect($copy)->toBeInstanceOf(CurlHandle::class);
            expect($copy)->not->toBe($handle);
            expect($copy->getInfo(CURLINFO_EFFECTIVE_URL))->toBe('https://example.com');
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->copy())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('reset', function (): void {
        it('resets all options', function (): void {
            $handle = new CurlHandle('https://example.com');
            $handle->setOption(CURLOPT_RETURNTRANSFER, true);
            $result = $handle->reset();

            expect($result)->toBe($handle); // chainable
            expect($handle->getInfo(CURLINFO_EFFECTIVE_URL))->toBe('');
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->reset())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });

    describe('close', function (): void {
        it('can be closed', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->getHandle())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });

        it('can be closed multiple times safely', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            $handle->close(); // should not throw
            expect(fn () => $handle->getHandle())
                ->toThrow(CurlException::class);
        });
    });

    describe('static methods', function (): void {
        it('returns version info', function (): void {
            $version = CurlHandle::version();
            expect($version)->toBeArray();
            expect($version)->toHaveKey('version');
            expect($version)->toHaveKey('protocols');
            expect($version['version'])->toBeString();
        });

        it('creates a CURLFile', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_file_');
            file_put_contents($tmpFile, 'test');

            try {
                $file = CurlHandle::createFile($tmpFile, 'text/plain', 'test.txt');
                expect($file)->toBeInstanceOf(\CURLFile::class);
                expect($file->getFilename())->toBe($tmpFile);
                expect($file->getMimeType())->toBe('text/plain');
                expect($file->getPostFilename())->toBe('test.txt');
            } finally {
                unlink($tmpFile);
            }
        });

        it('creates a CURLFile with defaults', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_file_');
            file_put_contents($tmpFile, 'test');

            try {
                $file = CurlHandle::createFile($tmpFile);
                expect($file)->toBeInstanceOf(\CURLFile::class);
                expect($file->getFilename())->toBe($tmpFile);
                expect($file->getMimeType())->toBe('');
            } finally {
                unlink($tmpFile);
            }
        });
    });

    describe('getHandle', function (): void {
        it('returns underlying CurlHandle', function (): void {
            $handle = new CurlHandle();
            expect($handle->getHandle())->toBeInstanceOf(\CurlHandle::class);
        });

        it('throws on closed handle', function (): void {
            $handle = new CurlHandle();
            $handle->close();
            expect(fn () => $handle->getHandle())
                ->toThrow(CurlException::class, 'CURL handle is closed');
        });
    });
});
