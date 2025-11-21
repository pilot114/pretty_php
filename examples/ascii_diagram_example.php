<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\TCPPacket;
use PrettyPhp\Binary\DNSPacket;

echo "=== ICMP Packet ASCII Diagram ===\n\n";
echo Binary::generateAsciiDiagram(ICMPPacket::class);
echo "\n\n";

echo "=== TCP Packet ASCII Diagram ===\n\n";
echo Binary::generateAsciiDiagram(TCPPacket::class);
echo "\n\n";

echo "=== DNS Packet ASCII Diagram ===\n\n";
echo Binary::generateAsciiDiagram(DNSPacket::class);
echo "\n\n";

// Example with custom structure
class CustomPacket
{
    public function __construct(
        #[Binary('8')]
        public int $type = 0,
        #[Binary('8')]
        public int $version = 1,
        #[Binary('16')]
        public int $length = 0,
        #[Binary('32')]
        public int $timestamp = 0,
        #[Binary('A*')]
        public string $payload = '',
    ) {
    }
}

echo "=== Custom Packet ASCII Diagram ===\n\n";
echo Binary::generateAsciiDiagram(CustomPacket::class);
