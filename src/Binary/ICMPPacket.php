<?php

namespace PrettyPhp\Binary;

class ICMPPacket
{
    use Checksum;

    public function __construct(
        #[Binary('C')] // 1 byte for ICMP message type
        public int $type,
        #[Binary('C')] // 1 byte for ICMP code
        public int $code,
        #[Binary('n')] // 2 bytes for checksum
        public int $checksum = 0,
        #[Binary('n')] // 2 bytes for identifier
        public int $identifier = 0,
        #[Binary('n')] // 2 bytes for sequence number
        public int $sequenceNumber = 0,
        #[Binary('A*')] // Request data (variable length)
        public string $data = '',
    ) {
        if ($checksum === 0) {
            $this->checksum = static::calculate(clone $this);
        }
    }
}
