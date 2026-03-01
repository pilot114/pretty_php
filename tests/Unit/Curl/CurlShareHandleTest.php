<?php

use PrettyPhp\Curl\CurlShareException;
use PrettyPhp\Curl\CurlShareHandle;

describe('CurlShareHandle', function (): void {
    describe('construction', function (): void {
        it('can be created', function (): void {
            $share = new CurlShareHandle();
            expect($share)->toBeInstanceOf(CurlShareHandle::class);
        });

        it('returns underlying handle', function (): void {
            $share = new CurlShareHandle();
            expect($share->getHandle())->toBeInstanceOf(\CurlShareHandle::class);
        });
    });

    describe('shareDns', function (): void {
        it('can share DNS cache', function (): void {
            $share = new CurlShareHandle();
            $result = $share->shareDns();
            expect($result)->toBe($share); // chainable
        });

        it('throws on closed handle', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->shareDns())
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });
    });

    describe('unshareDns', function (): void {
        it('can unshare DNS cache', function (): void {
            $share = new CurlShareHandle();
            $share->shareDns();
            $result = $share->unshareDns();
            expect($result)->toBe($share); // chainable
        });
    });

    describe('shareSslSession', function (): void {
        it('can share SSL sessions', function (): void {
            $share = new CurlShareHandle();
            $result = $share->shareSslSession();
            expect($result)->toBe($share); // chainable
        });

        it('throws on closed handle', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->shareSslSession())
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });
    });

    describe('unshareSslSession', function (): void {
        it('can unshare SSL sessions', function (): void {
            $share = new CurlShareHandle();
            $share->shareSslSession();
            $result = $share->unshareSslSession();
            expect($result)->toBe($share); // chainable
        });
    });

    describe('shareConnect', function (): void {
        it('can share connections', function (): void {
            $share = new CurlShareHandle();
            $result = $share->shareConnect();
            expect($result)->toBe($share); // chainable
        });

        it('throws on closed handle', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->shareConnect())
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });
    });

    describe('unshareConnect', function (): void {
        it('can unshare connections', function (): void {
            $share = new CurlShareHandle();
            $share->shareConnect();
            $result = $share->unshareConnect();
            expect($result)->toBe($share); // chainable
        });
    });

    describe('setOption', function (): void {
        it('can set share option', function (): void {
            $share = new CurlShareHandle();
            $result = $share->setOption(CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
            expect($result)->toBe($share); // chainable
        });

        it('throws on closed handle', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->setOption(CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS))
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });
    });

    describe('chaining', function (): void {
        it('supports method chaining for multiple share operations', function (): void {
            $share = new CurlShareHandle();
            $result = $share
                ->shareDns()
                ->shareSslSession()
                ->shareConnect();
            expect($result)->toBe($share);
        });
    });

    describe('error methods', function (): void {
        it('returns error number 0 when no error', function (): void {
            $share = new CurlShareHandle();
            expect($share->getErrorNumber())->toBe(0);
        });

        it('returns error string for known error code', function (): void {
            $result = CurlShareHandle::getErrorString(0);
            expect($result)->toBeString();
        });

        it('throws getErrorNumber on closed handle', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->getErrorNumber())
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });
    });

    describe('close', function (): void {
        it('can be closed', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            expect(fn () => $share->getHandle())
                ->toThrow(CurlShareException::class, 'CURL Share handle is closed');
        });

        it('can be closed multiple times safely', function (): void {
            $share = new CurlShareHandle();
            $share->close();
            $share->close(); // should not throw
            expect(fn () => $share->getHandle())
                ->toThrow(CurlShareException::class);
        });
    });
});
