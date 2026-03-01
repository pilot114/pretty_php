<?php

use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\RateLimitException;
use PrettyPhp\Binary\Security\SecurityException;
use PrettyPhp\Curl\CurlException;
use PrettyPhp\Curl\CurlMultiException;
use PrettyPhp\Curl\CurlShareException;
use PrettyPhp\Exception\ArrException;
use PrettyPhp\Exception\FileException;
use PrettyPhp\Exception\NumException;
use PrettyPhp\Exception\PathException;
use PrettyPhp\Exception\PrettyPhpException;

describe('Exception Hierarchy', function (): void {
    describe('PrettyPhpException', function (): void {
        it('extends RuntimeException', function (): void {
            $e = new PrettyPhpException('test');
            expect($e)->toBeInstanceOf(RuntimeException::class);
        });

        it('can be caught as RuntimeException', function (): void {
            $caught = false;
            try {
                throw new PrettyPhpException('test');
            } catch (RuntimeException) {
                $caught = true;
            }

            expect($caught)->toBeTrue();
        });
    });

    describe('Base module exceptions', function (): void {
        it('FileException extends PrettyPhpException', function (): void {
            $e = new FileException('file error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
            expect($e->getMessage())->toBe('file error');
        });

        it('PathException extends PrettyPhpException', function (): void {
            $e = new PathException('path error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
            expect($e->getMessage())->toBe('path error');
        });

        it('ArrException extends PrettyPhpException', function (): void {
            $e = new ArrException('arr error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
            expect($e->getMessage())->toBe('arr error');
        });

        it('NumException extends PrettyPhpException', function (): void {
            $e = new NumException('num error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
            expect($e->getMessage())->toBe('num error');
        });
    });

    describe('Curl module exceptions', function (): void {
        it('CurlException extends PrettyPhpException', function (): void {
            $e = new CurlException('curl error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
        });

        it('CurlMultiException extends PrettyPhpException', function (): void {
            $e = new CurlMultiException('curl multi error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
        });

        it('CurlShareException extends PrettyPhpException', function (): void {
            $e = new CurlShareException('curl share error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
        });
    });

    describe('Binary/Security module exceptions', function (): void {
        it('SecurityException extends PrettyPhpException', function (): void {
            $e = new SecurityException('security error');
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
            expect($e)->toBeInstanceOf(RuntimeException::class);
        });

        it('RateLimitException extends SecurityException', function (): void {
            $e = new RateLimitException('operation', 10, 60);
            expect($e)->toBeInstanceOf(SecurityException::class);
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
        });

        it('BufferOverflowException extends SecurityException', function (): void {
            $e = new BufferOverflowException(1024, 512);
            expect($e)->toBeInstanceOf(SecurityException::class);
            expect($e)->toBeInstanceOf(PrettyPhpException::class);
        });
    });

    describe('catch-all pattern', function (): void {
        it('can catch all Pretty PHP exceptions with one catch', function (): void {
            $exceptions = [
                new FileException('file'),
                new PathException('path'),
                new ArrException('arr'),
                new NumException('num'),
                new CurlException('curl'),
                new CurlMultiException('multi'),
                new CurlShareException('share'),
                new SecurityException('security'),
            ];

            foreach ($exceptions as $exception) {
                $caught = false;
                try {
                    throw $exception;
                } catch (PrettyPhpException) {
                    $caught = true;
                }

                expect($caught)->toBeTrue();
            }
        });
    });
});
