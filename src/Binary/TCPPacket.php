<?php

namespace PrettyPhp\Binary;

/**
 * TCP (Transmission Control Protocol) Packet
 * Based on RFC 793
 */
class TCPPacket
{
    use Checksum;

    public function __construct(
        #[Binary('n')] // 2 bytes for source port
        public int $sourcePort,
        #[Binary('n')] // 2 bytes for destination port
        public int $destinationPort,
        #[Binary('N')] // 4 bytes for sequence number
        public int $sequenceNumber,
        #[Binary('N')] // 4 bytes for acknowledgment number
        public int $acknowledgmentNumber = 0,
        #[Binary('n')] // 2 bytes: 4 bits data offset + 3 bits reserved + 9 bits flags
        public int $dataOffsetAndFlags = 0x5000, // Default: 5 (20 bytes header, no options)
        #[Binary('n')] // 2 bytes for window size
        public int $windowSize = 65535,
        #[Binary('n')] // 2 bytes for checksum
        public int $checksum = 0,
        #[Binary('n')] // 2 bytes for urgent pointer
        public int $urgentPointer = 0,
        #[Binary('A*')] // Data (variable length)
        public string $data = '',
    ) {
        if ($checksum === 0) {
            $this->checksum = static::calculate(clone $this);
        }
    }

    /**
     * Set TCP flags using individual flag values
     */
    public function setFlags(
        bool $fin = false,
        bool $syn = false,
        bool $rst = false,
        bool $psh = false,
        bool $ack = false,
        bool $urg = false,
        bool $ece = false,
        bool $cwr = false,
        bool $ns = false,
        int $dataOffset = 5
    ): void {
        $flags = 0;
        if ($fin) {
            $flags |= 0x01;
        }
        if ($syn) {
            $flags |= 0x02;
        }
        if ($rst) {
            $flags |= 0x04;
        }
        if ($psh) {
            $flags |= 0x08;
        }
        if ($ack) {
            $flags |= 0x10;
        }
        if ($urg) {
            $flags |= 0x20;
        }
        if ($ece) {
            $flags |= 0x40;
        }
        if ($cwr) {
            $flags |= 0x80;
        }
        if ($ns) {
            $flags |= 0x100;
        }

        $this->dataOffsetAndFlags = ($dataOffset << 12) | $flags;
    }
}
