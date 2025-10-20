<?php

namespace PrettyPhp\Binary;

class PacketPrinter
{
    /**
     * Print packet transmission information
     */
    public static function printTransmission(
        string $direction,
        string $data,
        ?string $label = null
    ): void {
        $arrow = $direction === 'send' ? '>>>' : '<<<';
        $header = $label !== null ? "$label $arrow" : $arrow;

        echo "\n" . str_repeat('─', 80) . "\n";
        echo "$header " . strlen($data) . " bytes\n";
        echo str_repeat('─', 80) . "\n";
        echo HexPrint::dump($data) . "\n";
    }

    public static function printICMPPacket(ICMPPacket $packet, string $label = 'ICMP Packet'): void
    {
        echo "\n┌─ $label " . str_repeat('─', 80 - strlen($label) - 4) . "┐\n";
        echo "│ Type:       {$packet->type}\n";
        echo "│ Code:       {$packet->code}\n";
        echo "│ Checksum:   0x" . sprintf('%04x', $packet->checksum) . "\n";
        echo "│ Identifier: {$packet->identifier}\n";
        echo "│ Sequence:   {$packet->sequenceNumber}\n";
        echo "│ Data:       " . strlen($packet->data) . " bytes\n";
        echo "└" . str_repeat('─', 79) . "┘\n";
    }

    public static function printIPPacket(IPPacket $packet, string $label = 'IP Packet'): void
    {
        $version = ($packet->versionAndHeaderLength >> 4) & 0x0F;
        $ihl = $packet->versionAndHeaderLength & 0x0F;
        $headerLength = $ihl * 4;

        echo "\n┌─ $label " . str_repeat('─', 80 - strlen($label) - 4) . "┐\n";
        echo "│ Version:        IPv$version\n";
        echo "│ Header Length:  $headerLength bytes\n";
        echo "│ Type of Service: {$packet->typeOfService}\n";
        echo "│ Total Length:   {$packet->totalLength} bytes\n";
        echo "│ Identification: {$packet->identification}\n";
        echo "│ TTL:            {$packet->ttl}\n";
        echo "│ Protocol:       " . self::getProtocolName($packet->protocol) . " ({$packet->protocol})\n";
        echo "│ Checksum:       0x" . sprintf('%04x', $packet->checksum) . "\n";
        echo "│ Source IP:      " . self::formatIP($packet->sourceIp) . "\n";
        echo "│ Destination IP: " . self::formatIP($packet->destinationIp) . "\n";
        echo "│ Payload:        " . strlen($packet->data) . " bytes\n";
        echo "└" . str_repeat('─', 79) . "┘\n";
    }

    public static function printResponseStats(PacketResponse $response): void
    {
        echo "\n┌─ Response Statistics " . str_repeat('─', 56) . "┐\n";

        if ($response->responseTimeMs !== null) {
            echo sprintf("│ Response Time: %.2f ms\n", $response->responseTimeMs);
        }

        if ($response->sourceIp !== null) {
            echo "│ Source:        {$response->sourceIp}";
            if ($response->sourcePort > 0) {
                echo ":{$response->sourcePort}";
            }
            echo "\n";
        }

        echo "│ Bytes Sent:    {$response->bytesSent}\n";
        echo "│ Bytes Received: {$response->bytesReceived}\n";

        echo "└" . str_repeat('─', 79) . "┘\n";
    }

    public static function printSection(string $title): void
    {
        echo "\n" . str_repeat('═', 80) . "\n";
        echo "  $title\n";
        echo str_repeat('═', 80) . "\n";
    }

    private static function formatIP(int $ip): string
    {
        if ($ip === 0) {
            return '0.0.0.0';
        }

        return long2ip($ip);
    }

    private static function getProtocolName(int $protocol): string
    {
        return match ($protocol) {
            1 => 'ICMP',
            6 => 'TCP',
            17 => 'UDP',
            41 => 'IPv6',
            47 => 'GRE',
            50 => 'ESP',
            51 => 'AH',
            58 => 'ICMPv6',
            default => 'Unknown',
        };
    }

    public static function printError(string $message): void
    {
        echo "\n┌─ ERROR " . str_repeat('─', 71) . "┐\n";
        echo "│ $message\n";
        echo "└" . str_repeat('─', 79) . "┘\n";
    }

    public static function printSuccess(string $message): void
    {
        echo "\n┌─ SUCCESS " . str_repeat('─', 68) . "┐\n";
        echo "│ $message\n";
        echo "└" . str_repeat('─', 79) . "┘\n";
    }
}
