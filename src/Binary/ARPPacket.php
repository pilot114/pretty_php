<?php

namespace PrettyPhp\Binary;

/**
 * ARP (Address Resolution Protocol) Packet
 * Based on RFC 826
 */
class ARPPacket
{
    public const HARDWARE_TYPE_ETHERNET = 1;

    public const PROTOCOL_TYPE_IPV4 = 0x0800;

    public const OPERATION_REQUEST = 1;

    public const OPERATION_REPLY = 2;

    public function __construct(
        #[Binary('n')] // 2 bytes for hardware type (1 = Ethernet)
        public int $hardwareType = self::HARDWARE_TYPE_ETHERNET,
        #[Binary('n')] // 2 bytes for protocol type (0x0800 = IPv4)
        public int $protocolType = self::PROTOCOL_TYPE_IPV4,
        #[Binary('C')] // 1 byte for hardware address length (6 for MAC)
        public int $hardwareAddressLength = 6,
        #[Binary('C')] // 1 byte for protocol address length (4 for IPv4)
        public int $protocolAddressLength = 4,
        #[Binary('n')] // 2 bytes for operation (1 = request, 2 = reply)
        public int $operation = self::OPERATION_REQUEST,
        #[Binary('A6')] // 6 bytes for sender hardware address (MAC)
        public string $senderHardwareAddress = "\x00\x00\x00\x00\x00\x00",
        #[Binary('N')] // 4 bytes for sender protocol address (IP)
        public int $senderProtocolAddress = 0,
        #[Binary('A6')] // 6 bytes for target hardware address (MAC)
        public string $targetHardwareAddress = "\x00\x00\x00\x00\x00\x00",
        #[Binary('N')] // 4 bytes for target protocol address (IP)
        public int $targetProtocolAddress = 0,
    ) {
    }

    /**
     * Convert MAC address string (e.g., "00:11:22:33:44:55") to binary format
     */
    public static function macToBinary(string $mac): string
    {
        $parts = explode(':', str_replace('-', ':', $mac));
        if (count($parts) !== 6) {
            throw new \Exception("Invalid MAC address format");
        }

        return pack('C6', ...array_map('hexdec', $parts));
    }

    /**
     * Convert binary MAC address to string format (e.g., "00:11:22:33:44:55")
     */
    public static function binaryToMac(string $binary): string
    {
        if (strlen($binary) !== 6) {
            throw new \Exception("Invalid binary MAC address length");
        }

        $bytes = unpack('C6', $binary);
        if ($bytes === false) {
            throw new \Exception("Failed to unpack MAC address");
        }

        /** @var array<int, int> $bytes */
        return implode(':', array_map(fn(int $b): string => sprintf('%02x', $b), $bytes));
    }
}
