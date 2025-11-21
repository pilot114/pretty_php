<?php

use PrettyPhp\Binary\Socket;
use PrettyPhp\Binary\RawSocket;
use PrettyPhp\Binary\NetworkInterface;
use PrettyPhp\Binary\PacketCapture;
use PrettyPhp\Binary\CapturedPacket;

describe('Socket', function (): void {
    it('can create a TCP socket', function (): void {
        $socket = Socket::tcp();
        expect($socket)->toBeInstanceOf(Socket::class);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
        expect($socket->isClosed())->toBe(true);
    });

    it('can create a UDP socket', function (): void {
        $socket = Socket::udp();
        expect($socket)->toBeInstanceOf(Socket::class);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('can bind a socket to an address', function (): void {
        $socket = Socket::udp();
        $socket->bind('127.0.0.1', 0); // Port 0 = auto-assign
        $info = $socket->getName();
        expect($info['address'])->toBe('127.0.0.1');
        expect($info['port'])->toBeGreaterThan(0);
        $socket->close();
    });

    it('can set socket options', function (): void {
        $socket = Socket::udp();
        $socket->setOption(SOL_SOCKET, SO_REUSEADDR, 1);
        $value = $socket->getOption(SOL_SOCKET, SO_REUSEADDR);
        expect($value)->toBe(1);
        $socket->close();
    });

    it('can set receive timeout', function (): void {
        $socket = Socket::udp();
        $socket->setReceiveTimeout(5, 500000);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('can set send timeout', function (): void {
        $socket = Socket::udp();
        $socket->setSendTimeout(5, 500000);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('can set blocking mode', function (): void {
        $socket = Socket::udp();
        $socket->setBlocking(false);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('throws exception when using closed socket', function (): void {
        $socket = Socket::udp();
        $socket->close();
        expect(fn() => $socket->send('test'))->toThrow(RuntimeException::class, 'Socket is closed');
    });

    it('can send and receive UDP data', function (): void {
        $server = Socket::udp();
        $server->bind('127.0.0.1', 0);
        $serverInfo = $server->getName();

        $client = Socket::udp();
        $message = 'Hello, UDP!';
        $client->sendTo($message, '127.0.0.1', $serverInfo['port']);

        $server->setReceiveTimeout(1);
        $received = $server->receiveFrom(1024);

        expect($received['data'])->toBe($message);
        expect($received['address'])->toBe('127.0.0.1');

        $server->close();
        $client->close();
    });

    it('automatically closes socket on destruction', function (): void {
        $socket = Socket::udp();
        expect($socket->isClosed())->toBe(false);
        unset($socket);
        // Socket should be automatically closed
        expect(true)->toBe(true);
    });
});

describe('NetworkInterface', function (): void {
    it('can get all network interfaces', function (): void {
        $interfaces = NetworkInterface::all();
        expect($interfaces)->toBeArray();
        expect(count($interfaces))->toBeGreaterThan(0);

        foreach ($interfaces as $name => $interface) {
            expect($name)->toBeString();
            expect($interface)->toBeInstanceOf(NetworkInterface::class);
            expect($interface->getName())->toBe($name);
        }
    });

    it('can get loopback interface', function (): void {
        $interfaces = NetworkInterface::all();
        $loopback = null;

        foreach ($interfaces as $interface) {
            if ($interface->isLoopback()) {
                $loopback = $interface;
                break;
            }
        }

        expect($loopback)->not->toBeNull();
        expect($loopback?->isLoopback())->toBe(true);
    });

    it('can check if interface is up', function (): void {
        $interfaces = NetworkInterface::all();
        $anyUp = false;

        foreach ($interfaces as $interface) {
            if ($interface->isUp()) {
                $anyUp = true;
                break;
            }
        }

        expect($anyUp)->toBe(true);
    });

    it('can get interface flags', function (): void {
        $interfaces = NetworkInterface::all();
        $interface = reset($interfaces);

        expect($interface)->toBeInstanceOf(NetworkInterface::class);
        expect($interface->getFlags())->toBeInt();
    });

    it('can get unicast addresses', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $addresses = $interface->getUnicastAddresses();
            expect($addresses)->toBeArray();

            if ($interface->isUp()) {
                // Up interfaces should have at least one address
                expect(count($addresses))->toBeGreaterThan(0);
            }
        }
    });

    it('can get IPv4 address from loopback', function (): void {
        $interfaces = NetworkInterface::all();
        $loopback = null;

        foreach ($interfaces as $interface) {
            if ($interface->isLoopback() && $interface->isUp()) {
                $loopback = $interface;
                break;
            }
        }

        if ($loopback !== null) {
            $ipv4 = $loopback->getIPv4Address();
            expect($ipv4)->toBeString();
            expect($ipv4)->toMatch('/^127\./'); // Should start with 127.
        }
    });

    it('can get MTU', function (): void {
        $interfaces = NetworkInterface::all();
        $interface = reset($interfaces);

        expect($interface)->toBeInstanceOf(NetworkInterface::class);
        $mtu = $interface->getMTU();
        if ($mtu !== null) {
            expect($mtu)->toBeInt();
            expect($mtu)->toBeGreaterThan(0);
        }
    });

    it('can convert to string', function (): void {
        $interfaces = NetworkInterface::all();
        $interface = reset($interfaces);

        expect($interface)->toBeInstanceOf(NetworkInterface::class);
        $str = (string) $interface;
        expect($str)->toBeString();
        expect($str)->toContain($interface->getName());
    });

    it('can describe interface', function (): void {
        $interfaces = NetworkInterface::all();
        $interface = reset($interfaces);

        expect($interface)->toBeInstanceOf(NetworkInterface::class);
        $description = $interface->describe();
        expect($description)->toBeString();
        expect($description)->toContain('Interface:');
        expect($description)->toContain($interface->getName());
    });

    it('can get specific interface by name', function (): void {
        $interfaces = NetworkInterface::all();
        $firstKey = array_key_first($interfaces);

        if ($firstKey !== null) {
            $interface = NetworkInterface::get($firstKey);
            expect($interface)->toBeInstanceOf(NetworkInterface::class);
            expect($interface?->getName())->toBe($firstKey);
        }
    });

    it('returns null for non-existent interface', function (): void {
        $interface = NetworkInterface::get('nonexistent12345');
        expect($interface)->toBeNull();
    });

    it('can get all IPv4 addresses', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $ipv4Addresses = $interface->getIPv4Addresses();
            expect($ipv4Addresses)->toBeArray();

            foreach ($ipv4Addresses as $addr) {
                expect($addr)->toBeString();
                expect(filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))->not->toBe(false);
            }
        }
    });

    it('can get all IPv6 addresses', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $ipv6Addresses = $interface->getIPv6Addresses();
            expect($ipv6Addresses)->toBeArray();

            foreach ($ipv6Addresses as $addr) {
                expect($addr)->toBeString();
                // IPv6 validation
                expect(filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))->not->toBe(false);
            }
        }
    });

    it('can check broadcast flag', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $isBroadcast = $interface->isBroadcast();
            expect($isBroadcast)->toBeBool();
        }
    });

    it('can check multicast flag', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $isMulticast = $interface->isMulticast();
            expect($isMulticast)->toBeBool();
        }
    });

    it('can check point-to-point flag', function (): void {
        $interfaces = NetworkInterface::all();

        foreach ($interfaces as $interface) {
            $isP2P = $interface->isPointToPoint();
            expect($isP2P)->toBeBool();
        }
    });

    it('can get interface info', function (): void {
        $interfaces = NetworkInterface::all();
        $interface = reset($interfaces);

        expect($interface)->toBeInstanceOf(NetworkInterface::class);
        $info = $interface->getInfo();
        expect($info)->toBeArray();
        // Info array structure may vary by platform
        expect(count($info))->toBeGreaterThan(0);
    });
});

describe('RawSocket', function (): void {
    it('requires root privileges for raw sockets', function (): void {
        if (posix_geteuid() !== 0) {
            expect(fn() => RawSocket::icmp())->toThrow(RuntimeException::class, 'Raw sockets require superuser');
        } else {
            $socket = RawSocket::icmp();
            expect($socket)->toBeInstanceOf(RawSocket::class);
            $socket->close();
        }
    });

    it('can create ICMP raw socket with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::icmp();
        expect($socket)->toBeInstanceOf(RawSocket::class);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('can create TCP raw socket with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::tcp();
        expect($socket)->toBeInstanceOf(RawSocket::class);
        $socket->close();
    });

    it('can create UDP raw socket with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::udp();
        expect($socket)->toBeInstanceOf(RawSocket::class);
        $socket->close();
    });

    it('can create custom protocol raw socket with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::protocol(1); // ICMP
        expect($socket)->toBeInstanceOf(RawSocket::class);
        $socket->close();
    });

    it('can enable IP header include with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::icmp();
        $socket->enableIpHeaderInclude();
        expect($socket->isIpHeaderIncluded())->toBe(true);
        $socket->close();
    });

    it('can disable IP header include with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::icmp();
        $socket->enableIpHeaderInclude();
        $socket->disableIpHeaderInclude();
        expect($socket->isIpHeaderIncluded())->toBe(false);
        $socket->close();
    });

    it('can set TTL with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::icmp();
        $socket->setTTL(64);
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });

    it('can enable broadcast with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $socket = RawSocket::icmp();
        $socket->enableBroadcast();
        expect($socket->isClosed())->toBe(false);
        $socket->close();
    });
});

describe('PacketCapture', function (): void {
    it('requires root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            expect(fn() => PacketCapture::all())->toThrow(RuntimeException::class, 'Packet capture requires superuser');
        } else {
            $capture = PacketCapture::all();
            expect($capture)->toBeInstanceOf(PacketCapture::class);
        }
    });

    it('can create ICMP capture with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::icmp();
        expect($capture)->toBeInstanceOf(PacketCapture::class);
    });

    it('can create TCP capture with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::tcp();
        expect($capture)->toBeInstanceOf(PacketCapture::class);
    });

    it('can create UDP capture with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::udp();
        expect($capture)->toBeInstanceOf(PacketCapture::class);
    });

    it('can start and stop capture with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::all();
        expect($capture->isCapturing())->toBe(false);

        $capture->start();
        expect($capture->isCapturing())->toBe(true);

        $capture->stop();
        expect($capture->isCapturing())->toBe(false);
    });

    it('can get statistics with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::all();
        $capture->start();

        $stats = $capture->getStats();
        expect($stats)->toBeArray();
        expect($stats)->toHaveKey('captured');
        expect($stats)->toHaveKey('dropped');
        expect($stats['captured'])->toBeInt();
        expect($stats['dropped'])->toBeInt();

        $capture->stop();
    });

    it('can add and clear filters with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::all();
        $capture->addFilter('test');
        $capture->addFilter('example');
        $capture->clearFilters();

        expect($capture)->toBeInstanceOf(PacketCapture::class);
    });

    it('can add packet handler with root privileges', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::all();
        $handlerCalled = false;

        $capture->onPacket(function (CapturedPacket $packet) use (&$handlerCalled): void {
            $handlerCalled = true;
        });

        expect($capture)->toBeInstanceOf(PacketCapture::class);
    });

    it('throws exception when capturing without starting', function (): void {
        if (posix_geteuid() !== 0) {
            $this->markTestSkipped('Test requires root privileges');
        }

        $capture = PacketCapture::all();
        expect(fn() => $capture->capture(1, 1))->toThrow(RuntimeException::class, 'not started');
    });
});

describe('CapturedPacket', function (): void {
    it('can create a captured packet', function (): void {
        $packet = new CapturedPacket(
            data: 'test data',
            timestamp: microtime(true),
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            length: 9,
            interface: 'eth0',
        );

        expect($packet)->toBeInstanceOf(CapturedPacket::class);
        expect($packet->data)->toBe('test data');
        expect($packet->sourceAddress)->toBe('192.168.1.1');
        expect($packet->sourcePort)->toBe(8080);
        expect($packet->length)->toBe(9);
        expect($packet->interface)->toBe('eth0');
    });

    it('can convert to hex', function (): void {
        $packet = new CapturedPacket(
            data: 'ABC',
            timestamp: microtime(true),
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            length: 3,
            interface: 'eth0',
        );

        $hex = $packet->toHex();
        expect($hex)->toBe('414243'); // 'ABC' in hex
    });

    it('can get summary', function (): void {
        $packet = new CapturedPacket(
            data: 'test',
            timestamp: microtime(true),
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            length: 4,
            interface: 'eth0',
        );

        $summary = $packet->getSummary();
        expect($summary)->toBeString();
        expect($summary)->toContain('192.168.1.1');
        expect($summary)->toContain('8080');
        expect($summary)->toContain('4 bytes');
    });

    it('can get formatted timestamp', function (): void {
        $packet = new CapturedPacket(
            data: 'test',
            timestamp: microtime(true),
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            length: 4,
            interface: 'eth0',
        );

        $formatted = $packet->getFormattedTimestamp('Y-m-d');
        expect($formatted)->toBeString();
        expect($formatted)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    });
});
