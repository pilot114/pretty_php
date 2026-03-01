<?php

use PrettyPhp\Curl\Curl;
use PrettyPhp\Curl\CurlHandle;
use PrettyPhp\Curl\CurlMultiHandle;
use PrettyPhp\Curl\CurlShareHandle;

describe('Curl', function (): void {
    describe('init', function (): void {
        it('creates a CurlHandle without url', function (): void {
            $handle = Curl::init();
            expect($handle)->toBeInstanceOf(CurlHandle::class);
        });

        it('creates a CurlHandle with url', function (): void {
            $handle = Curl::init('https://example.com');
            expect($handle)->toBeInstanceOf(CurlHandle::class);
            expect($handle->getInfo(CURLINFO_EFFECTIVE_URL))->toBe('https://example.com');
        });
    });

    describe('multi', function (): void {
        it('creates a CurlMultiHandle', function (): void {
            $multi = Curl::multi();
            expect($multi)->toBeInstanceOf(CurlMultiHandle::class);
        });
    });

    describe('share', function (): void {
        it('creates a CurlShareHandle', function (): void {
            $share = Curl::share();
            expect($share)->toBeInstanceOf(CurlShareHandle::class);
        });
    });

    describe('file', function (): void {
        it('creates a CURLFile', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_factory_');
            file_put_contents($tmpFile, 'test');

            try {
                $file = Curl::file($tmpFile, 'text/plain', 'test.txt');
                expect($file)->toBeInstanceOf(\CURLFile::class);
                expect($file->getFilename())->toBe($tmpFile);
                expect($file->getMimeType())->toBe('text/plain');
                expect($file->getPostFilename())->toBe('test.txt');
            } finally {
                unlink($tmpFile);
            }
        });

        it('creates a CURLFile with defaults', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_factory_');
            file_put_contents($tmpFile, 'test');

            try {
                $file = Curl::file($tmpFile);
                expect($file)->toBeInstanceOf(\CURLFile::class);
                expect($file->getFilename())->toBe($tmpFile);
            } finally {
                unlink($tmpFile);
            }
        });
    });

    describe('version', function (): void {
        it('returns version info', function (): void {
            $version = Curl::version();
            expect($version)->toBeArray();
            expect($version)->toHaveKey('version');
            expect($version)->toHaveKey('protocols');
            expect($version)->toHaveKey('host');
            expect($version['version'])->toBeString();
            expect($version['protocols'])->toBeArray();
        });
    });
});
