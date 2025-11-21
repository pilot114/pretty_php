<?php

namespace PrettyPhp\Binary;

/**
 * UDP (User Datagram Protocol) Packet
 * Based on RFC 768
 */
class UDPPacket
{
    use Checksum;

    public function __construct(
        #[Binary('n')] // 2 bytes for source port
        public int $sourcePort,
        #[Binary('n')] // 2 bytes for destination port
        public int $destinationPort,
        #[Binary('n')] // 2 bytes for length (header + data)
        public int $length = 0,
        #[Binary('n')] // 2 bytes for checksum
        public int $checksum = 0,
        #[Binary('A*')] // Data (variable length)
        public string $data = '',
    ) {
        if ($length === 0) {
            // UDP header is 8 bytes + data length
            $this->length = 8 + strlen($data);
        }

        if ($checksum === 0) {
            $this->checksum = static::calculate(clone $this);
        }
    }
}
