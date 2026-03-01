<?php

use PrettyPhp\Curl\CurlException;
use PrettyPhp\Curl\CurlHandle;
use PrettyPhp\Curl\CurlMultiException;
use PrettyPhp\Curl\CurlMultiHandle;

describe('CurlMultiHandle', function (): void {
    describe('construction', function (): void {
        it('can be created', function (): void {
            $multi = new CurlMultiHandle();
            expect($multi)->toBeInstanceOf(CurlMultiHandle::class);
        });

        it('returns underlying handle', function (): void {
            $multi = new CurlMultiHandle();
            expect($multi->getHandle())->toBeInstanceOf(\CurlMultiHandle::class);
        });
    });

    describe('addHandle', function (): void {
        it('can add a curl handle', function (): void {
            $multi = new CurlMultiHandle();
            $handle = new CurlHandle();
            $result = $multi->addHandle($handle);
            expect($result)->toBe($multi); // chainable
        });

        it('throws on closed multi handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            $handle = new CurlHandle();
            expect(fn () => $multi->addHandle($handle))
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('removeHandle', function (): void {
        it('can remove a curl handle', function (): void {
            $multi = new CurlMultiHandle();
            $handle = new CurlHandle();
            $multi->addHandle($handle);
            $result = $multi->removeHandle($handle);
            expect($result)->toBe($multi); // chainable
        });

        it('throws on closed multi handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            $handle = new CurlHandle();
            expect(fn () => $multi->removeHandle($handle))
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('execute', function (): void {
        it('can execute with no handles', function (): void {
            $multi = new CurlMultiHandle();
            $stillRunning = 0;
            $result = $multi->execute($stillRunning);
            expect($result)->toBe(CURLM_OK);
            expect($stillRunning)->toBe(0);
        });

        it('throws on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            $stillRunning = 0;
            expect(fn () => $multi->execute($stillRunning))
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('select', function (): void {
        it('can select with no handles', function (): void {
            $multi = new CurlMultiHandle();
            $result = $multi->select(0.001);
            expect($result)->toBeInt();
        });

        it('throws on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->select())
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('getContent', function (): void {
        it('returns null for handle without execution', function (): void {
            $multi = new CurlMultiHandle();
            $handle = new CurlHandle();
            $handle->setOption(CURLOPT_RETURNTRANSFER, true);
            $multi->addHandle($handle);
            $content = $multi->getContent($handle);
            expect($content)->toBe('');
        });

        it('throws on closed multi handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            $handle = new CurlHandle();
            expect(fn () => $multi->getContent($handle))
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('infoRead', function (): void {
        it('returns false when no messages', function (): void {
            $multi = new CurlMultiHandle();
            $messagesInQueue = 0;
            $result = $multi->infoRead($messagesInQueue);
            expect($result)->toBeFalse();
        });

        it('throws on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->infoRead())
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('setOption', function (): void {
        it('can set an option', function (): void {
            $multi = new CurlMultiHandle();
            $result = $multi->setOption(CURLMOPT_MAXCONNECTS, 10);
            expect($result)->toBe($multi); // chainable
        });

        it('throws on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->setOption(CURLMOPT_MAXCONNECTS, 10))
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('error methods', function (): void {
        it('returns error number 0 when no error', function (): void {
            $multi = new CurlMultiHandle();
            expect($multi->getErrorNumber())->toBe(0);
        });

        it('returns error string for known error code', function (): void {
            $result = CurlMultiHandle::getErrorString(CURLM_OK);
            expect($result)->toBeString();
        });

        it('throws getErrorNumber on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->getErrorNumber())
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('executeAll', function (): void {
        it('executes file:// requests and collects results', function (): void {
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_multi_');
            file_put_contents($tmpFile, 'multi test content');

            try {
                $multi = new CurlMultiHandle();
                $handle = new CurlHandle('file://' . $tmpFile);
                $handle->setOption(CURLOPT_RETURNTRANSFER, true);
                $multi->addHandle($handle);

                $results = $multi->executeAll();
                expect($results)->toBeArray();
                expect($results)->toHaveCount(1);
                expect($results[0]['content'])->toBe('multi test content');
                expect($results[0]['result'])->toBe(0);
                expect($results[0]['handle'])->toBe($handle);
            } finally {
                unlink($tmpFile);
            }
        });

        it('executes multiple file:// requests', function (): void {
            $tmpFile1 = tempnam(sys_get_temp_dir(), 'curl_multi1_');
            $tmpFile2 = tempnam(sys_get_temp_dir(), 'curl_multi2_');
            file_put_contents($tmpFile1, 'content1');
            file_put_contents($tmpFile2, 'content2');

            try {
                $multi = new CurlMultiHandle();

                $handle1 = new CurlHandle('file://' . $tmpFile1);
                $handle1->setOption(CURLOPT_RETURNTRANSFER, true);
                $multi->addHandle($handle1);

                $handle2 = new CurlHandle('file://' . $tmpFile2);
                $handle2->setOption(CURLOPT_RETURNTRANSFER, true);
                $multi->addHandle($handle2);

                $results = $multi->executeAll();
                expect($results)->toHaveCount(2);

                $contents = array_map(fn ($r) => $r['content'], $results);
                sort($contents);
                expect($contents)->toBe(['content1', 'content2']);
            } finally {
                unlink($tmpFile1);
                unlink($tmpFile2);
            }
        });

        it('returns empty array with no handles', function (): void {
            $multi = new CurlMultiHandle();
            $results = $multi->executeAll();
            expect($results)->toBe([]);
        });

        it('throws on closed handle', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->executeAll())
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });
    });

    describe('close', function (): void {
        it('can be closed', function (): void {
            $multi = new CurlMultiHandle();
            $multi->close();
            expect(fn () => $multi->getHandle())
                ->toThrow(CurlMultiException::class, 'CURL Multi handle is closed');
        });

        it('can be closed multiple times safely', function (): void {
            $multi = new CurlMultiHandle();
            $handle = new CurlHandle();
            $multi->addHandle($handle);
            $multi->close();
            $multi->close(); // should not throw
            expect(fn () => $multi->getHandle())
                ->toThrow(CurlMultiException::class);
        });
    });
});
