<?php

declare(strict_types=1);

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\DNSPacket;
use PrettyPhp\Binary\HexPrint;
use PrettyPhp\Binary\NetworkInterface;

mutates(HexPrint::class, DNSPacket::class, NetworkInterface::class);

describe('HexPrint exact output', function (): void {
    it('dumps every byte value', function (): void {
        $all = implode('', array_map(chr(...), range(0, 255)));
        expect(HexPrint::dump($all))->toMatchSnapshot();
        expect(HexPrint::colorDump($all))->toMatchSnapshot();
    });

    it('pads short lines and groups blocks', function (): void {
        expect(HexPrint::dump('ABC', 4))->toBe('00000000  41 42 43     |ABC|');
        expect(HexPrint::colorDump('A', 2))->toBe("\033[36m00000000\033[0m  \033[92m41\033[0m     |A|");
        expect(HexPrint::toBlocks(implode('', array_map(chr(...), range(0, 19)))))
            ->toBe("00010203 04050607 08090a0b 0c0d0e0f\n10111213");
    });
});

describe('DNSPacket header flags', function (): void {
    it('uses RFC 1035 constant values', function (): void {
        expect([DNSPacket::QR_QUERY, DNSPacket::QR_RESPONSE])->toBe([0, 1]);
        expect([DNSPacket::OPCODE_QUERY, DNSPacket::OPCODE_IQUERY, DNSPacket::OPCODE_STATUS])->toBe([0, 1, 2]);
        expect([
            DNSPacket::RCODE_NO_ERROR,
            DNSPacket::RCODE_FORMAT_ERROR,
            DNSPacket::RCODE_SERVER_FAILURE,
            DNSPacket::RCODE_NAME_ERROR,
            DNSPacket::RCODE_NOT_IMPLEMENTED,
            DNSPacket::RCODE_REFUSED,
        ])->toBe([0, 1, 2, 3, 4, 5]);
    });

    it('places every flag on its bit', function (array $args, int $expected): void {
        $packet = new DNSPacket(transactionId: 1);
        $packet->setFlags(...$args);

        expect($packet->flags)->toBe($expected);
    })->with([
        'defaults (RD)' => [[], 0x0100],
        'response' => [['qr' => 1, 'rd' => false], 0x8000],
        'qr is one bit' => [['qr' => 3, 'rd' => false], 0x8000],
        'opcode status' => [['opcode' => 2, 'rd' => false], 0x1000],
        'opcode is four bits' => [['opcode' => 0x1F, 'rd' => false], 0x7800],
        'authoritative' => [['aa' => true, 'rd' => false], 0x0400],
        'truncated' => [['tc' => true, 'rd' => false], 0x0200],
        'recursion available' => [['ra' => true, 'rd' => false], 0x0080],
        'nothing' => [['rd' => false], 0x0000],
        'rcode refused' => [['rcode' => 5, 'rd' => false], 0x0005],
        'rcode is four bits' => [['rcode' => 0x1F, 'rd' => false], 0x000F],
    ]);

    it('reads flags back', function (): void {
        $packet = new DNSPacket(transactionId: 1, flags: 0x8000 | (2 << 11) | 0x0100 | 0x0080 | 0x0003);
        expect($packet->getQR())->toBe(1);
        expect($packet->getOpcode())->toBe(2);
        expect($packet->getRCode())->toBe(3);
        expect($packet->isRecursionDesired())->toBeTrue();
        expect($packet->isRecursionAvailable())->toBeTrue();

        $empty = new DNSPacket(transactionId: 1, flags: 0xFFFF & ~0x0180);
        expect($empty->getQR())->toBe(1);
        expect($empty->getOpcode())->toBe(0xF);
        expect($empty->getRCode())->toBe(0xF);
        expect($empty->isRecursionDesired())->toBeFalse();
        expect($empty->isRecursionAvailable())->toBeFalse();
    });

    it('reads recursion bits independently', function (): void {
        $desired = new DNSPacket(transactionId: 1, flags: 0x0100);
        expect([$desired->getQR(), $desired->isRecursionDesired(), $desired->isRecursionAvailable()])->toBe([0, true, false]);
        $available = new DNSPacket(transactionId: 1, flags: 0x0080);
        expect([$available->isRecursionDesired(), $available->isRecursionAvailable()])->toBe([false, true]);
    });

    it('has an empty header by default', function (): void {
        expect(bin2hex(Binary::pack(new DNSPacket(transactionId: 0x1234))))->toBe('123401000000000000000000');
    });
});

describe('NetworkInterface details', function (): void {
    beforeEach(function (): void {
        class_exists(NetworkInterface::class);
        $this->make = \Closure::bind(
            static fn (string $name, array $info): NetworkInterface => new NetworkInterface($name, $info),
            null,
            NetworkInterface::class
        );
    });

    it('uses Linux interface flag values', function (): void {
        expect([IFF_UP, IFF_BROADCAST, IFF_LOOPBACK, IFF_POINTOPOINT, IFF_MULTICAST])->toBe([0x1, 0x2, 0x8, 0x10, 0x1000]);
    });

    it('describes interfaces exactly', function (): void {
        $eth = ($this->make)('eth0', [
            'up' => 1,
            'flags' => IFF_UP | IFF_BROADCAST | IFF_MULTICAST,
            'mtu' => 1500,
            'hwaddr' => 'aa:bb:cc:dd:ee:ff',
            'unicast' => [
                ['family' => AF_INET, 'address' => '10.0.0.1'],
                ['family' => AF_INET, 'address' => '10.0.0.2'],
                ['family' => AF_INET6, 'address' => 'fe80::1'],
            ],
        ]);

        expect((string) $eth)->toBe('eth0: UP IPv4:10.0.0.1 IPv6:fe80::1');
        expect($eth->getMacAddress())->toBe('aa:bb:cc:dd:ee:ff');
        expect($eth->getFlags())->toBe(IFF_UP | IFF_BROADCAST | IFF_MULTICAST);
        expect($eth->describe())->toBe(implode("\n", [
            'Interface: eth0',
            '  Status: UP',
            '  Type: Ethernet',
            '  MTU: 1500',
            '  MAC: aa:bb:cc:dd:ee:ff',
            '  IPv4: 10.0.0.1, 10.0.0.2',
            '  IPv6: fe80::1',
            '  Flags: BROADCAST, MULTICAST',
        ]));

        $down = ($this->make)('lo', ['up' => 0, 'unicast' => [['family' => AF_INET, 'flags' => IFF_LOOPBACK]]]);
        expect((string) $down)->toBe('lo: DOWN');
        expect($down->isUp())->toBeFalse();
        expect($down->getFlags())->toBe(0);
        expect($down->describe())->toBe("Interface: lo\n  Status: DOWN\n  Type: Loopback");
    });

    it('reads flags from any unicast entry', function (): void {
        $iface = ($this->make)('x', ['unicast' => [['family' => AF_INET], 'bogus', ['flags' => IFF_MULTICAST]]]);
        expect($iface->isMulticast())->toBeTrue();
        expect($iface->isBroadcast())->toBeFalse();
        expect($iface->getIPv4Addresses())->toBe([]);
        expect(($this->make)('y', ['flags' => 'invalid'])->getFlags())->toBe(0);
        expect(($this->make)('z', ['flags' => IFF_UP])->isUp())->toBeTrue();
    });
});
