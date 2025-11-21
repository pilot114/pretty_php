<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

/**
 * CapturedPacket - Represents a captured network packet
 */
readonly class CapturedPacket
{
    public function __construct(
        public string $data,
        public float $timestamp,
        public string $sourceAddress,
        public int $sourcePort,
        public int $length,
        public string $interface,
    ) {
    }

    /**
     * Parse the packet data as a specific packet type
     *
     * @template T of object
     * @param class-string<T> $packetClass
     * @return T
     */
    public function parse(string $packetClass): object
    {
        return Binary::unpack($this->data, $packetClass);
    }

    /**
     * Parse as IP packet
     */
    public function parseIP(): IPPacket
    {
        return Binary::unpack($this->data, IPPacket::class);
    }

    /**
     * Parse as ICMP packet (skipping IP header)
     */
    public function parseICMP(int $ipHeaderSize = 20): ICMPPacket
    {
        $icmpData = substr($this->data, $ipHeaderSize);
        return Binary::unpack($icmpData, ICMPPacket::class);
    }

    /**
     * Parse as TCP packet (skipping IP header)
     */
    public function parseTCP(int $ipHeaderSize = 20): TCPPacket
    {
        $tcpData = substr($this->data, $ipHeaderSize);
        return Binary::unpack($tcpData, TCPPacket::class);
    }

    /**
     * Parse as UDP packet (skipping IP header)
     */
    public function parseUDP(int $ipHeaderSize = 20): UDPPacket
    {
        $udpData = substr($this->data, $ipHeaderSize);
        return Binary::unpack($udpData, UDPPacket::class);
    }

    /**
     * Get packet data as hexadecimal string
     */
    public function toHex(): string
    {
        return bin2hex($this->data);
    }

    /**
     * Get formatted timestamp
     */
    public function getFormattedTimestamp(string $format = 'Y-m-d H:i:s.u'): string
    {
        $dt = \DateTime::createFromFormat('U.u', (string) $this->timestamp);
        return $dt !== false ? $dt->format($format) : '';
    }

    /**
     * Get a summary of the packet
     */
    public function getSummary(): string
    {
        return sprintf(
            '[%s] %s:%d (%d bytes)',
            $this->getFormattedTimestamp('H:i:s.u'),
            $this->sourceAddress,
            $this->sourcePort,
            $this->length
        );
    }
}
