<?php

use PrettyPhp\Curl\CurlException;
use PrettyPhp\Curl\CurlHandle;
use PrettyPhp\Curl\CurlMultiException;
use PrettyPhp\Curl\CurlMultiHandle;
use PrettyPhp\Curl\CurlShareException;
use PrettyPhp\Curl\CurlShareHandle;

describe('CurlException', function (): void {
    it('can be constructed with message only', function (): void {
        $e = new CurlException('test error');
        expect($e->getMessage())->toBe('test error');
        expect($e->curlCode)->toBe(0);
        expect($e->getPrevious())->toBeNull();
    });

    it('can be constructed with curl code', function (): void {
        $e = new CurlException('test error', 7);
        expect($e->getMessage())->toBe('test error');
        expect($e->curlCode)->toBe(7);
        expect($e->getCode())->toBe(7);
    });

    it('can be constructed with previous exception', function (): void {
        $prev = new RuntimeException('previous');
        $e = new CurlException('test error', 0, $prev);
        expect($e->getPrevious())->toBe($prev);
    });

    it('extends RuntimeException', function (): void {
        $e = new CurlException('test');
        expect($e)->toBeInstanceOf(RuntimeException::class);
    });

    it('can be created from a handle with error', function (): void {
        $handle = new CurlHandle();
        $handle->setOption(CURLOPT_URL, 'invalid://url');
        $handle->setOption(CURLOPT_RETURNTRANSFER, true);
        $handle->setOption(CURLOPT_CONNECTTIMEOUT_MS, 1);

        try {
            $handle->execute();
        } catch (CurlException $e) {
            expect($e->curlCode)->toBeGreaterThan(0);
            expect($e->getMessage())->not->toBe('');
        }
    });
});

describe('CurlMultiException', function (): void {
    it('can be constructed with message only', function (): void {
        $e = new CurlMultiException('multi error');
        expect($e->getMessage())->toBe('multi error');
        expect($e->curlMultiCode)->toBe(0);
        expect($e->getPrevious())->toBeNull();
    });

    it('can be constructed with curl multi code', function (): void {
        $e = new CurlMultiException('multi error', 6);
        expect($e->curlMultiCode)->toBe(6);
        expect($e->getCode())->toBe(6);
    });

    it('can be constructed with previous exception', function (): void {
        $prev = new RuntimeException('previous');
        $e = new CurlMultiException('multi error', 0, $prev);
        expect($e->getPrevious())->toBe($prev);
    });

    it('extends RuntimeException', function (): void {
        $e = new CurlMultiException('test');
        expect($e)->toBeInstanceOf(RuntimeException::class);
    });
});

describe('CurlShareException', function (): void {
    it('can be constructed with message only', function (): void {
        $e = new CurlShareException('share error');
        expect($e->getMessage())->toBe('share error');
        expect($e->curlShareCode)->toBe(0);
        expect($e->getPrevious())->toBeNull();
    });

    it('can be constructed with curl share code', function (): void {
        $e = new CurlShareException('share error', 3);
        expect($e->curlShareCode)->toBe(3);
        expect($e->getCode())->toBe(3);
    });

    it('can be constructed with previous exception', function (): void {
        $prev = new RuntimeException('previous');
        $e = new CurlShareException('share error', 0, $prev);
        expect($e->getPrevious())->toBe($prev);
    });

    it('extends RuntimeException', function (): void {
        $e = new CurlShareException('test');
        expect($e)->toBeInstanceOf(RuntimeException::class);
    });
});
