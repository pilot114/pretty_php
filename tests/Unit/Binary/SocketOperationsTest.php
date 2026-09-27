<?php

declare(strict_types=1);

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\RawSocket;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\Security\SecurityAudit;
use PrettyPhp\Binary\Socket;

/**
 * @return array{0: Socket, 1: Socket, 2: Socket} Listening server, connected client and accepted connection
 */
mutates(\PrettyPhp\Binary\Socket::class, \PrettyPhp\Binary\RawSocket::class, \PrettyPhp\Binary\Security\SecurityAudit::class);

function tcpPair(): array
{
    $server = Socket::tcp()->bind('127.0.0.1')->listen();
    $client = Socket::tcp()->connect('127.0.0.1', $server->getName()['port']);

    return [$server, $client, $server->accept()];
}

/**
 * RawSocket instance backed by a UDP socket, so it can be exercised without root privileges
 */
function udpRawSocket(): RawSocket
{
    return new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
}

describe('Socket operations', function (): void {
    it('throws when socket cannot be created', function (): void {
        expect(fn (): Socket => @new Socket(AF_INET, SOCK_STREAM, SOL_UDP))
            ->toThrow(\RuntimeException::class, 'Failed to create socket');
    });

    it('requires root for raw sockets', function (): void {
        expect(fn (): Socket => Socket::raw(1))->toThrow(\RuntimeException::class, 'superuser');
    })->skip(fn (): bool => posix_geteuid() === 0, 'running as root');

    it('accepts TCP connections and exchanges data', function (): void {
        [$server, $client, $connection] = tcpPair();

        expect($client->send('ping'))->toBe(4);
        expect($connection->receive(4))->toBe('ping');
        expect($client->getPeerName()['port'])->toBe($server->getName()['port']);
        expect($connection->getResource())->toBeInstanceOf(\Socket::class);

        $server->close();
        $client->close();
        $connection->close();
        $connection->close();

        expect($connection->isClosed())->toBeTrue();
    });

    it('applies rate limiter to all transfer operations', function (): void {
        [$server, $client, $connection] = tcpPair();
        $client->setRateLimiter(new RateLimiter(100, 60));
        $connection->setRateLimiter(new RateLimiter(100, 60));

        $client->send('a');
        expect($connection->receive(1))->toBe('a');

        $udpServer = Socket::udp()->bind('127.0.0.1')->setRateLimiter(new RateLimiter(100, 60));
        $udpClient = Socket::udp()->setRateLimiter(new RateLimiter(100, 60));
        $udpClient->sendTo('b', '127.0.0.1', $udpServer->getName()['port']);

        expect($udpServer->setReceiveTimeout(1)->receiveFrom(10)['data'])->toBe('b');

        $limited = Socket::udp()->setRateLimiter(new RateLimiter(1, 60));
        $limited->sendTo('c', '127.0.0.1', $udpServer->getName()['port']);

        expect(fn (): int => $limited->sendTo('d', '127.0.0.1', 9))
            ->toThrow(\PrettyPhp\Binary\Security\RateLimitException::class);

        foreach ([$server, $client, $connection, $udpServer, $udpClient, $limited] as $socket) {
            $socket->close();
        }
    });

    it('reports failures of socket operations', function (): void {
        $udp = Socket::udp();
        expect(fn (): Socket => @$udp->bind('203.0.113.1', 1))->toThrow(\RuntimeException::class, 'Failed to bind socket');
        expect(fn (): Socket => @$udp->listen())->toThrow(\RuntimeException::class, 'Failed to listen on socket');
        expect(fn (): int => @$udp->send('x'))->toThrow(\RuntimeException::class, 'Failed to send data');
        expect(fn (): int => @$udp->sendTo('x', 'not-an-ip', 1))->toThrow(\RuntimeException::class, 'Failed to send data');
        expect(fn (): array => @$udp->getPeerName())->toThrow(\RuntimeException::class, 'Failed to get peer name');
        expect(fn (): Socket => $udp->setOption(SOL_SOCKET, SO_REUSEADDR, 1.5))
            ->toThrow(\RuntimeException::class, 'Invalid socket option value type');
        expect(fn (): Socket => @$udp->setOption(SOL_SOCKET, 99999, 1))
            ->toThrow(\RuntimeException::class, 'Failed to set socket option');
        expect(fn (): mixed => @$udp->getOption(SOL_SOCKET, 99999))
            ->toThrow(\RuntimeException::class, 'Failed to get socket option');

        $udp->setBlocking(false)->setBlocking(true)->setBlocking(false);
        expect(fn (): string => @$udp->receive(10))->toThrow(\RuntimeException::class, 'Failed to receive data');
        expect(fn (): array => @$udp->receiveFrom(10))->toThrow(\RuntimeException::class, 'Failed to receive data');

        $udp->close();

        $tcp = Socket::tcp();
        expect(fn (): Socket => @$tcp->connect('127.0.0.1', 1))->toThrow(\RuntimeException::class, 'Failed to connect socket');
        $tcp->close();

        $listener = Socket::tcp()->bind('127.0.0.1')->listen()->setBlocking(false);
        expect(fn (): Socket => @$listener->accept())->toThrow(\RuntimeException::class, 'Failed to accept connection');
        $listener->close();
    });
});

describe('RawSocket over UDP', function (): void {
    it('requires root for raw socket factories', function (string $factory, array $args): void {
        expect(fn (): RawSocket => RawSocket::$factory(...$args))->toThrow(\RuntimeException::class, 'superuser');
    })->with([
        ['icmp', []],
        ['tcp', []],
        ['udp', []],
        ['protocol', [1]],
    ])->skip(fn (): bool => posix_geteuid() === 0, 'running as root');

    it('sets IP level options', function (): void {
        $socket = udpRawSocket();
        $socket->setTTL(32)->setTOS(0)->enableBroadcast()->disableBroadcast();
        $socket->setIpOptions(['ttl' => 16, 'tos' => 0, 'recvttl' => 1, 'recvtos' => 1]);
        expect($socket->getOption(IPPROTO_IP, IP_TTL))->toBe(16);

        expect(fn (): RawSocket => $socket->setIpOptions(['bogus' => 1]))
            ->toThrow(\RuntimeException::class, 'Unknown IP option: bogus');
        expect(fn (): RawSocket => $socket->enablePromiscuousMode())
            ->toThrow(\RuntimeException::class, 'Promiscuous mode is not yet implemented');
        $socket->close();
    });

    it('rejects IP header inclusion on non-raw sockets', function (): void {
        $socket = udpRawSocket();
        expect(fn (): RawSocket => @$socket->enableIpHeaderInclude())
            ->toThrow(\RuntimeException::class, 'Failed to set socket option');
        expect(fn (): RawSocket => @$socket->disableIpHeaderInclude())
            ->toThrow(\RuntimeException::class, 'Failed to set socket option');
        expect($socket->isIpHeaderIncluded())->toBeFalse();

        $socket->close();
    });

    it('binds to network interface', function (): void {
        $socket = udpRawSocket();
        expect($socket->bindToInterface('lo'))->toBe($socket);
        $socket->close();
    });

    it('sends packets and receives responses', function (): void {
        $socket = udpRawSocket()->bind('127.0.0.1');
        $port = $socket->getName()['port'];
        $packet = new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1);

        $sent = $socket->sendPacket($packet, '127.0.0.1', $port);
        expect($sent->bytesSent)->toBe(strlen(Binary::pack($packet)));
        expect($sent->hasResponse())->toBeFalse();

        $received = $socket->receivePacket(ICMPPacket::class);
        expect($received['packet']->type)->toBe(8);

        $response = $socket->sendAndReceive($packet, '127.0.0.1', $port);
        expect($response->hasResponse())->toBeTrue();
        expect($response->sourcePort)->toBe($port);

        $socket->close();
    });

    it('returns empty response on receive timeout', function (): void {
        $socket = udpRawSocket()->bind('127.0.0.1');
        $sink = Socket::udp()->bind('127.0.0.1');
        $packet = new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1);

        $response = @$socket->sendAndReceive($packet, '127.0.0.1', $sink->getName()['port'], timeoutSeconds: 1);
        expect($response->hasResponse())->toBeFalse();
        expect($response->bytesSent)->toBeGreaterThan(0);

        $socket->close();
        $sink->close();
    });

    it('is reported as critical by security audit', function (): void {
        $socket = udpRawSocket();
        $limiter = new RateLimiter(1, 1);
        $limiter->disable();

        $socket->setRateLimiter($limiter);

        $messages = array_column(new SecurityAudit()->auditSocket($socket), 'message');
        expect($messages)->toContain('Socket has rate limiter but it is disabled')
            ->toContain('Using raw socket which requires elevated privileges');
        $socket->close();
    });
});
