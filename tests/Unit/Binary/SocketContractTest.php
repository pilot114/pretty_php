<?php

declare(strict_types=1);

use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\RawSocket;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\Security\RateLimitException;
use PrettyPhp\Binary\Socket;

mutates(Socket::class, RawSocket::class);

describe('Socket contract', function (): void {
    it('rejects every operation on a closed socket', function (Closure $operation): void {
        $socket = Socket::udp();
        $socket->close();

        expect(fn (): mixed => $operation($socket))->toThrow(\RuntimeException::class, 'Socket is closed');
    })->with([
        'bind' => [fn (Socket $s): Socket => $s->bind('127.0.0.1')],
        'connect' => [fn (Socket $s): Socket => $s->connect('127.0.0.1', 9)],
        'listen' => [fn (Socket $s): Socket => $s->listen()],
        'accept' => [fn (Socket $s): Socket => $s->accept()],
        'send' => [fn (Socket $s): int => $s->send('x')],
        'sendTo' => [fn (Socket $s): int => $s->sendTo('x', '127.0.0.1', 9)],
        'receive' => [fn (Socket $s): string => $s->receive(1)],
        'receiveFrom' => [fn (Socket $s): array => $s->receiveFrom(1)],
        'setOption' => [fn (Socket $s): Socket => $s->setOption(SOL_SOCKET, SO_REUSEADDR, 1)],
        'getOption' => [fn (Socket $s): mixed => $s->getOption(SOL_SOCKET, SO_REUSEADDR)],
        'setBlocking' => [fn (Socket $s): Socket => $s->setBlocking(true)],
        'getName' => [fn (Socket $s): array => $s->getName()],
        'getPeerName' => [fn (Socket $s): array => $s->getPeerName()],
        'getResource' => [fn (Socket $s): \Socket => $s->getResource()],
    ]);

    it('applies the rate limiter to every transfer operation', function (): void {
        $server = Socket::udp()->bind('127.0.0.1');
        $port = $server->getName()['port'];

        $client = Socket::udp()->connect('127.0.0.1', $port)->setRateLimiter(new RateLimiter(1, 60));
        $client->send('a');

        expect(fn (): int => $client->send('b'))->toThrow(RateLimitException::class, 'socket send');

        $server->setReceiveTimeout(1)->setRateLimiter(new RateLimiter(1, 60));
        expect($server->receiveFrom(10)['data'])->toBe('a');
        expect(fn (): array => $server->receiveFrom(10))->toThrow(RateLimitException::class, 'socket receiveFrom');

        $receiver = Socket::udp()->bind('127.0.0.1')->setReceiveTimeout(1)->setRateLimiter(new RateLimiter(1, 60));
        Socket::udp()->sendTo('c', '127.0.0.1', $receiver->getName()['port']);
        expect($receiver->receive(10))->toBe('c');
        expect(fn (): string => $receiver->receive(10))->toThrow(RateLimitException::class, 'socket receive');
    });

    it('reports local and peer addresses', function (): void {
        $server = Socket::tcp()->bind('127.0.0.1')->listen(1);
        $client = Socket::tcp()->connect('127.0.0.1', $server->getName()['port']);
        $connection = $server->accept();

        expect($client->getPeerName())->toBe(['address' => '127.0.0.1', 'port' => $server->getName()['port']]);
        expect($connection->getPeerName())->toBe($client->getName());
        expect($connection->isClosed())->toBeFalse();
        expect($connection->getRateLimiter())->toBeNull();
    });

    it('releases the port when closed or destroyed', function (): void {
        $socket = Socket::udp()->bind('127.0.0.1');
        $port = $socket->getName()['port'];
        $socket->close();
        expect(Socket::udp()->bind('127.0.0.1', $port)->getName()['port'])->toBe($port);

        $destroyed = Socket::udp()->bind('127.0.0.1');
        $port = $destroyed->getName()['port'];
        unset($destroyed);
        expect(Socket::udp()->bind('127.0.0.1', $port)->getName()['port'])->toBe($port);
    });

    it('sets timeouts with zero microseconds by default', function (): void {
        $socket = Socket::udp()->setReceiveTimeout(5)->setSendTimeout(6);
        expect($socket->getOption(SOL_SOCKET, SO_RCVTIMEO))->toBe(['sec' => 5, 'usec' => 0]);
        expect($socket->getOption(SOL_SOCKET, SO_SNDTIMEO))->toBe(['sec' => 6, 'usec' => 0]);
    });

    it('includes the system error in failure messages', function (Closure $operation, string $prefix): void {
        $message = null;
        try {
            @$operation();
        } catch (\RuntimeException $runtimeException) {
            $message = $runtimeException->getMessage();
        }

        // anchored prefix followed by a non-empty system reason
        expect($message)->toMatch('/^' . preg_quote($prefix, '/') . ': \S.+$/');
    })->with([
        'create' => [fn (): Socket => new Socket(AF_INET, SOCK_STREAM, SOL_UDP), 'Failed to create socket'],
        'bind' => [fn (): Socket => Socket::udp()->bind('203.0.113.1', 1), 'Failed to bind socket'],
        'connect' => [fn (): Socket => Socket::tcp()->connect('127.0.0.1', 1), 'Failed to connect socket'],
        'listen' => [fn (): Socket => Socket::udp()->listen(), 'Failed to listen on socket'],
        'accept' => [fn (): Socket => Socket::tcp()->bind('127.0.0.1')->listen()->setBlocking(false)->accept(), 'Failed to accept connection'],
        'send' => [fn (): int => Socket::udp()->send('x'), 'Failed to send data'],
        'sendTo' => [fn (): int => Socket::udp()->sendTo('x', 'not-an-ip', 1), 'Failed to send data'],
        'receive' => [fn (): string => Socket::udp()->setBlocking(false)->receive(1), 'Failed to receive data'],
        'receiveFrom' => [fn (): array => Socket::udp()->setBlocking(false)->receiveFrom(1), 'Failed to receive data'],
        'setOption' => [fn (): Socket => Socket::udp()->setOption(SOL_SOCKET, 99999, 1), 'Failed to set socket option'],
        'getOption' => [fn (): mixed => Socket::udp()->getOption(SOL_SOCKET, 99999), 'Failed to get socket option'],
        'getPeerName' => [fn (): array => Socket::udp()->getPeerName(), 'Failed to get peer name'],
    ]);
});

describe('RawSocket options and responses', function (): void {
    it('defines portable option constants', function (): void {
        class_exists(RawSocket::class);
        expect([IP_TTL, IP_TOS, IP_RECVTTL, IP_RECVTOS, IP_HDRINCL, SO_BINDTODEVICE])->toBe([2, 1, 12, 13, 3, 25]);
    })->skip(PHP_OS_FAMILY !== 'Linux', 'constant values are Linux specific');

    it('applies TTL, TOS and broadcast options', function (): void {
        $socket = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        expect($socket->setTTL(33)->getOption(IPPROTO_IP, IP_TTL))->toBe(33);
        expect($socket->setTOS(16)->getOption(IPPROTO_IP, IP_TOS))->toBe(16);
        expect($socket->enableBroadcast()->getOption(SOL_SOCKET, SO_BROADCAST))->toBe(1);
        expect($socket->disableBroadcast()->getOption(SOL_SOCKET, SO_BROADCAST))->toBe(0);
    });

    it('binds to a network interface', function (): void {
        $socket = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        expect(fn (): RawSocket => @$socket->bindToInterface('no-such-interface-0'))
            ->toThrow(\RuntimeException::class, 'Failed to set socket option');
    });

    it('fills packet responses', function (): void {
        $socket = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        $socket->bind('127.0.0.1');

        $port = $socket->getName()['port'];
        $packet = new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1);

        $sent = $socket->sendPacket($packet, '127.0.0.1', $port);
        expect($sent->responseData)->toBeNull();
        expect($sent->sourceIp)->toBeNull();
        expect($sent->sourcePort)->toBe(0);
        expect($sent->bytesReceived)->toBe(0);
        expect($sent->responseTimeMs)->toBeGreaterThanOrEqual(0.0)->toBeLessThan(1000.0);

        $raw = $socket->receivePacket();
        expect($raw)->not->toHaveKey('packet');
        expect($raw['port'])->toBe($port);

        $response = $socket->sendAndReceive($packet, '127.0.0.1', $port);
        expect($response->bytesReceived)->toBe(strlen((string) $response->responseData));
        expect($response->responseTimeMs)->toBeGreaterThanOrEqual(0.0)->toBeLessThan(1000.0);
    });

    it('fills timeout responses', function (): void {
        $socket = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        $socket->bind('127.0.0.1');

        $sink = Socket::udp()->bind('127.0.0.1');
        $packet = new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1);

        $response = @$socket->sendAndReceive($packet, '127.0.0.1', $sink->getName()['port'], timeoutSeconds: 1);
        expect($response->sourceIp)->toBeNull();
        expect($response->sourcePort)->toBe(0);
        expect($response->bytesReceived)->toBe(0);
        expect($response->responseTimeMs)->toBeGreaterThanOrEqual(900.0)->toBeLessThan(5000.0);
    });
});
