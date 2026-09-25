<?php

declare(strict_types=1);

use PrettyPhp\Binary\NetworkInterface;

// Loading the class defines the IFF_* constants used below
class_exists(NetworkInterface::class);

/**
 * @param array<string, mixed> $info
 */
function makeInterface(string $name, array $info): NetworkInterface
{
    $factory = \Closure::bind(
        static fn (string $name, array $info): NetworkInterface => new NetworkInterface($name, $info),
        null,
        NetworkInterface::class
    );

    return $factory($name, $info);
}

describe('NetworkInterface info shapes', function (): void {
    it('reads flags from top-level field', function (): void {
        $iface = makeInterface('eth9', [
            'flags' => IFF_UP | IFF_BROADCAST | IFF_MULTICAST,
            'mtu' => 1500,
            'mac' => 'aa:bb:cc:dd:ee:ff',
            'stats' => ['rx_bytes' => 10],
            'unicast' => [
                'garbage',
                ['family' => AF_INET, 'address' => '10.0.0.1'],
                ['family' => AF_INET6, 'address' => 'fe80::1'],
            ],
        ]);

        expect($iface->isUp())->toBeTrue();
        expect($iface->getStats())->toBe(['rx_bytes' => 10]);
        expect($iface->getIPv4Address())->toBe('10.0.0.1');
        expect($iface->getIPv6Address())->toBe('fe80::1');
        expect($iface->getIPv4Addresses())->toBe(['10.0.0.1']);
        expect($iface->getIPv6Addresses())->toBe(['fe80::1']);

        expect($iface->describe())
            ->toContain('Type: Ethernet')
            ->toContain('MTU: 1500')
            ->toContain('MAC: aa:bb:cc:dd:ee:ff')
            ->toContain('Flags: BROADCAST, MULTICAST');
    });

    it('describes point-to-point links', function (): void {
        $iface = makeInterface('ppp0', ['unicast' => [['flags' => IFF_POINTOPOINT]]]);
        expect($iface->describe())->toContain('Type: Point-to-Point');
        expect($iface->isUp())->toBeFalse();
    });

    it('handles malformed unicast data', function (): void {
        $iface = makeInterface('bad0', ['unicast' => 'invalid']);
        expect($iface->isLoopback())->toBeFalse();
        expect(makeInterface('bad1', ['unicast' => ['garbage']])->isLoopback())->toBeFalse();
        expect($iface->getStats())->toBeNull();
        expect($iface->getIPv4Address())->toBeNull();
        expect($iface->getIPv6Address())->toBeNull();
    });
});
