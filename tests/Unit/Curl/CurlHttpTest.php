<?php

declare(strict_types=1);

use PrettyPhp\Curl\Curl;
use PrettyPhp\Curl\CurlException;
use PrettyPhp\Curl\CurlHandle;
use PrettyPhp\Curl\CurlMultiException;
use PrettyPhp\Curl\CurlMultiHandle;
use PrettyPhp\Curl\CurlShareException;
use PrettyPhp\Curl\CurlShareHandle;

/**
 * Start PHP built-in server with echo router and return [process, base URL]
 *
 * @return array{0: resource, 1: string}
 */
mutates(\PrettyPhp\Curl\Curl::class, \PrettyPhp\Curl\CurlHandle::class, \PrettyPhp\Curl\CurlMultiHandle::class, \PrettyPhp\Curl\CurlShareHandle::class, \PrettyPhp\Curl\CurlMultiException::class, \PrettyPhp\Curl\CurlShareException::class);

function startEchoServer(): array
{
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr((string) stream_socket_get_name($probe, false), strrpos((string) stream_socket_get_name($probe, false), ':') + 1);
    fclose($probe);

    $process = proc_open(
        [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/../../Support/http-echo.php'],
        [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
        $pipes
    );

    for ($i = 0; $i < 100; $i++) {
        $connection = @fsockopen('127.0.0.1', $port);
        if ($connection !== false) {
            fclose($connection);
            break;
        }

        usleep(20000);
    }

    return [$process, 'http://127.0.0.1:' . $port];
}

beforeAll(function (): void {
    [$GLOBALS['echoServer'], $GLOBALS['echoUrl']] = startEchoServer();
});

afterAll(function (): void {
    proc_terminate($GLOBALS['echoServer']);
    proc_close($GLOBALS['echoServer']);
});

/**
 * @return array{method: string, body: string, headers: array<string, string>}
 */
function echoed(string $response): array
{
    return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
}

describe('Curl HTTP helpers', function (): void {
    it('sends GET with headers', function (): void {
        $response = echoed(Curl::get($GLOBALS['echoUrl'], ['X-Test' => 'value']));
        expect($response['method'])->toBe('GET');
        expect($response['headers']['x-test'])->toBe('value');
    });

    it('sends POST with form data', function (): void {
        $response = echoed(Curl::post($GLOBALS['echoUrl'], ['a' => '1', 'b' => '2']));
        expect($response['method'])->toBe('POST');
        expect($response['body'])->toBe('a=1&b=2');
    });

    it('sends PUT with raw string body', function (): void {
        $response = echoed(Curl::put($GLOBALS['echoUrl'], 'raw-body'));
        expect($response['method'])->toBe('PUT');
        expect($response['body'])->toBe('raw-body');
    });

    it('sends PATCH with JSON encoded object', function (): void {
        $payload = new \stdClass();
        $payload->name = 'x';

        $response = echoed(Curl::patch($GLOBALS['echoUrl'], $payload));
        expect($response['method'])->toBe('PATCH');
        expect($response['body'])->toBe('{"name":"x"}');
    });

    it('sends DELETE and HEAD', function (): void {
        expect(echoed(Curl::delete($GLOBALS['echoUrl']))['method'])->toBe('DELETE');
        expect(Curl::head($GLOBALS['echoUrl']))->toBe('');
    });

    it('executes multiple requests in parallel with select', function (): void {
        $multi = new CurlMultiHandle();
        $first = new CurlHandle($GLOBALS['echoUrl'] . '/?sleep=100000');
        $first->setOption(CURLOPT_RETURNTRANSFER, true);

        $second = new CurlHandle($GLOBALS['echoUrl'] . '/?sleep=50000');
        $second->setOption(CURLOPT_RETURNTRANSFER, true);

        $results = $multi->addHandle($first)->addHandle($second)->executeAll();
        expect($results)->toHaveCount(2);
        expect(echoed((string) $results[0]['content'])['method'])->toBe('GET');

        $multi->close();
    });
});

describe('Curl failure handling', function (): void {
    it('reports invalid option values', function (): void {
        $handle = new CurlHandle();
        expect(fn (): CurlHandle => @$handle->setOption(CURLOPT_SSLVERSION, 999))
            ->toThrow(CurlException::class, 'Failed to set CURL option');
        expect(fn (): CurlHandle => @$handle->setOptions([CURLOPT_SSLVERSION => 999]))
            ->toThrow(CurlException::class, 'Failed to set CURL options');
    });

    it('reports pause failure and performs upkeep', function (): void {
        $handle = new CurlHandle();
        expect(fn (): CurlHandle => $handle->pause(CURLPAUSE_ALL))
            ->toThrow(CurlException::class, 'Failed to pause/unpause connection');
        expect($handle->upkeep())->toBe($handle);
    });

    it('reports multi handle failures', function (): void {
        $multi = new CurlMultiHandle();
        $handle = new CurlHandle('file:///etc/hostname');
        $multi->addHandle($handle);
        expect(fn (): CurlMultiHandle => $multi->addHandle($handle))
            ->toThrow(CurlMultiException::class, 'Failed to add handle');

        $exception = CurlMultiException::fromHandle($multi->getHandle());
        expect($exception->curlMultiCode)->toBeGreaterThan(0);
    });

    it('reports multi option failures', function (): void {
        $multi = new CurlMultiHandle();
        expect(fn (): CurlMultiHandle => @$multi->setOption(CURLMOPT_MAX_HOST_CONNECTIONS, -5))
            ->toThrow(CurlMultiException::class, 'Failed to set multi option');
    })->skip(
        // Older libcurl (e.g. 8.14 in the official PHP image) accepts any value for this option
        fn (): bool => @curl_multi_setopt(curl_multi_init(), CURLMOPT_MAX_HOST_CONNECTIONS, -5),
        'this libcurl accepts a negative CURLMOPT_MAX_HOST_CONNECTIONS'
    );

    it('suppresses errors when destroying multi handle with closed handles', function (): void {
        $multi = new CurlMultiHandle();
        $handle = new CurlHandle('file:///etc/hostname');
        $multi->addHandle($handle);
        $handle->close();

        unset($multi);
        expect(true)->toBeTrue();
    });

    it('reports share handle failures', function (): void {
        $share = new CurlShareHandle();
        expect(fn (): CurlShareHandle => @$share->setOption(CURLSHOPT_SHARE, 9999))
            ->toThrow(CurlShareException::class, 'Failed to set share option');

        $exception = CurlShareException::fromHandle($share->getHandle());
        expect($exception->getMessage())->toBeString();
    });
});

describe('Curl pause during transfer', function (): void {
    it('pauses and resumes an active transfer', function (): void {
        $handle = new CurlHandle('file:///etc/hostname');
        $paused = null;
        $handle->setOption(CURLOPT_WRITEFUNCTION, function ($ch, string $data) use ($handle, &$paused): int {
            $paused = $handle->pause(CURLPAUSE_CONT);
            return strlen($data);
        });
        $handle->execute();

        expect($paused)->toBe($handle);
    });
});
