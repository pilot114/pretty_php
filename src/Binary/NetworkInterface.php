<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use RuntimeException;

// Define network interface flags if not defined
if (!defined('IFF_UP')) {
    define('IFF_UP', 0x1);
}
if (!defined('IFF_BROADCAST')) {
    define('IFF_BROADCAST', 0x2);
}
if (!defined('IFF_LOOPBACK')) {
    define('IFF_LOOPBACK', 0x8);
}
if (!defined('IFF_POINTOPOINT')) {
    define('IFF_POINTOPOINT', 0x10);
}
if (!defined('IFF_MULTICAST')) {
    define('IFF_MULTICAST', 0x1000);
}

/**
 * NetworkInterface - Network interface information
 *
 * Provides information about network interfaces on the system.
 */
readonly class NetworkInterface
{
    /**
     * @param array<string, mixed> $info
     */
    private function __construct(
        public string $name,
        public array $info
    ) {
    }

    /**
     * Get all network interfaces
     *
     * @return array<string, self>
     * @throws RuntimeException
     */
    public static function all(): array
    {
        $interfaces = net_get_interfaces();
        if ($interfaces === false) {
            throw new RuntimeException('Failed to get network interfaces');
        }

        /** @var array<string, self> $result */
        $result = [];
        foreach ($interfaces as $name => $info) {
            assert(is_string($name));
            assert(is_array($info));
            $result[$name] = new self($name, $info);
        }

        return $result;
    }

    /**
     * Get a specific network interface by name
     *
     * @throws RuntimeException
     */
    public static function get(string $name): ?self
    {
        $interfaces = self::all();
        return $interfaces[$name] ?? null;
    }

    /**
     * Get the interface name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the interface is up
     */
    public function isUp(): bool
    {
        $flags = is_int($this->info['flags'] ?? 0) ? ($this->info['flags'] ?? 0) : 0;
        return (bool) ($flags & IFF_UP);
    }

    /**
     * Check if the interface is a loopback interface
     */
    public function isLoopback(): bool
    {
        $flags = is_int($this->info['flags'] ?? 0) ? ($this->info['flags'] ?? 0) : 0;
        return (bool) ($flags & IFF_LOOPBACK);
    }

    /**
     * Check if the interface supports broadcast
     */
    public function isBroadcast(): bool
    {
        $flags = is_int($this->info['flags'] ?? 0) ? ($this->info['flags'] ?? 0) : 0;
        return (bool) ($flags & IFF_BROADCAST);
    }

    /**
     * Check if the interface is a point-to-point link
     */
    public function isPointToPoint(): bool
    {
        $flags = is_int($this->info['flags'] ?? 0) ? ($this->info['flags'] ?? 0) : 0;
        return (bool) ($flags & IFF_POINTOPOINT);
    }

    /**
     * Check if the interface supports multicast
     */
    public function isMulticast(): bool
    {
        $flags = is_int($this->info['flags'] ?? 0) ? ($this->info['flags'] ?? 0) : 0;
        return (bool) ($flags & IFF_MULTICAST);
    }

    /**
     * Get the interface flags
     */
    public function getFlags(): int
    {
        $flags = $this->info['flags'] ?? 0;
        return is_int($flags) ? $flags : 0;
    }

    /**
     * Get unicast addresses
     *
     * @return array<array{address: string, netmask?: string, family?: int}>
     */
    public function getUnicastAddresses(): array
    {
        $unicast = $this->info['unicast'] ?? [];
        return is_array($unicast) ? $unicast : [];
    }

    /**
     * Get the first IPv4 address
     */
    public function getIPv4Address(): ?string
    {
        foreach ($this->getUnicastAddresses() as $addr) {
            if (!is_array($addr)) {
                continue;
            }
            if (($addr['family'] ?? 0) === AF_INET) {
                $address = $addr['address'] ?? null;
                return is_string($address) ? $address : null;
            }
        }

        return null;
    }

    /**
     * Get the first IPv6 address
     */
    public function getIPv6Address(): ?string
    {
        foreach ($this->getUnicastAddresses() as $addr) {
            if (!is_array($addr)) {
                continue;
            }
            if (($addr['family'] ?? 0) === AF_INET6) {
                $address = $addr['address'] ?? null;
                return is_string($address) ? $address : null;
            }
        }

        return null;
    }

    /**
     * Get all IPv4 addresses
     *
     * @return array<string>
     */
    public function getIPv4Addresses(): array
    {
        $addresses = [];
        foreach ($this->getUnicastAddresses() as $addr) {
            if (!is_array($addr)) {
                continue;
            }
            if (($addr['family'] ?? 0) === AF_INET && isset($addr['address']) && is_string($addr['address'])) {
                $addresses[] = $addr['address'];
            }
        }

        return $addresses;
    }

    /**
     * Get all IPv6 addresses
     *
     * @return array<string>
     */
    public function getIPv6Addresses(): array
    {
        $addresses = [];
        foreach ($this->getUnicastAddresses() as $addr) {
            if (!is_array($addr)) {
                continue;
            }
            if (($addr['family'] ?? 0) === AF_INET6 && isset($addr['address']) && is_string($addr['address'])) {
                $addresses[] = $addr['address'];
            }
        }

        return $addresses;
    }

    /**
     * Get the MTU (Maximum Transmission Unit)
     */
    public function getMTU(): ?int
    {
        $mtu = $this->info['mtu'] ?? null;
        return is_int($mtu) ? $mtu : null;
    }

    /**
     * Get the MAC address
     */
    public function getMacAddress(): ?string
    {
        // Try to get MAC address from the interface info
        // The exact key may vary by platform
        $mac = $this->info['mac'] ?? $this->info['hwaddr'] ?? null;
        return is_string($mac) ? $mac : null;
    }

    /**
     * Get statistics
     *
     * @return array{rx_bytes?: int, tx_bytes?: int, rx_packets?: int, tx_packets?: int, rx_errors?: int, tx_errors?: int}|null
     */
    public function getStats(): ?array
    {
        $stats = $this->info['stats'] ?? null;
        return is_array($stats) ? $stats : null;
    }

    /**
     * Get all information about the interface
     *
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Convert to string representation
     */
    public function __toString(): string
    {
        $ipv4 = $this->getIPv4Address();
        $ipv6 = $this->getIPv6Address();
        $status = $this->isUp() ? 'UP' : 'DOWN';

        $str = "{$this->name}: {$status}";

        if ($ipv4 !== null) {
            $str .= " IPv4:{$ipv4}";
        }

        if ($ipv6 !== null) {
            $str .= " IPv6:{$ipv6}";
        }

        return $str;
    }

    /**
     * Get a human-readable description of the interface
     */
    public function describe(): string
    {
        $lines = [];
        $lines[] = "Interface: {$this->name}";
        $lines[] = "  Status: " . ($this->isUp() ? 'UP' : 'DOWN');

        if ($this->isLoopback()) {
            $lines[] = "  Type: Loopback";
        } elseif ($this->isPointToPoint()) {
            $lines[] = "  Type: Point-to-Point";
        } else {
            $lines[] = "  Type: Ethernet";
        }

        $mtu = $this->getMTU();
        if ($mtu !== null) {
            $lines[] = "  MTU: {$mtu}";
        }

        $mac = $this->getMacAddress();
        if ($mac !== null) {
            $lines[] = "  MAC: {$mac}";
        }

        $ipv4Addresses = $this->getIPv4Addresses();
        if ($ipv4Addresses !== []) {
            $lines[] = "  IPv4: " . implode(', ', $ipv4Addresses);
        }

        $ipv6Addresses = $this->getIPv6Addresses();
        if ($ipv6Addresses !== []) {
            $lines[] = "  IPv6: " . implode(', ', $ipv6Addresses);
        }

        $flags = [];
        if ($this->isBroadcast()) {
            $flags[] = 'BROADCAST';
        }
        if ($this->isMulticast()) {
            $flags[] = 'MULTICAST';
        }
        if ($flags !== []) {
            $lines[] = "  Flags: " . implode(', ', $flags);
        }

        return implode("\n", $lines);
    }
}
