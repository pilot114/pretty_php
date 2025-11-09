<?php

namespace PrettyPhp\Binary;

class IPPacket
{
    use Checksum;

    public function __construct(
        #[Binary('C')] // 4 bits version + 4 bits header length
        public int $versionAndHeaderLength,
        #[Binary('C')] // 1 byte for type of service
        public int $typeOfService,
        #[Binary('n')] // 2 bytes for total packet length
        public int $totalLength,
        #[Binary('n')] // 2 bytes for packet identification
        public int $identification,
        #[Binary('n')] // 3 bits flags and 13 bits fragment offset
        public int $flagsAndFragmentOffset,
        #[Binary('C')] // 1 byte for time to live (TTL)
        public int $ttl,
        #[Binary('C')] // 1 byte for protocol
        public int $protocol,
        #[Binary('n')] // 2 bytes for header checksum
        public int $checksum = 0,
        #[Binary('N')] // 4 bytes for source IP address
        public int $sourceIp = 0,
        #[Binary('N')] // 4 bytes for destination IP address
        public int $destinationIp = 0,
        #[Binary('A*')] // Request data (variable length)
        public string $data = '',
    ) {
        if ($checksum === 0) {
            $this->checksum = static::calculate(clone $this);
        }
    }
}
