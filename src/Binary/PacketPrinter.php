<?php

declare(strict_types=1);

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
        $header = $label !== null ? sprintf('%s %s', $label, $arrow) : $arrow;

        echo "\n" . str_repeat('─', 80) . "\n";
        echo $header . ' ' . strlen($data) . " bytes\n";
        echo str_repeat('─', 80) . "\n";
        echo HexPrint::dump($data) . "\n";
    }

    public static function printICMPPacket(ICMPPacket $packet, string $label = 'ICMP Packet'): void
    {
        echo self::boxTop($label);
        echo sprintf('│ Type:       %d%s', $packet->type, PHP_EOL);
        echo sprintf('│ Code:       %d%s', $packet->code, PHP_EOL);
        echo "│ Checksum:   0x" . sprintf('%04x', $packet->checksum) . "\n";
        echo sprintf('│ Identifier: %d%s', $packet->identifier, PHP_EOL);
        echo sprintf('│ Sequence:   %d%s', $packet->sequenceNumber, PHP_EOL);
        echo "│ Data:       " . strlen($packet->data) . " bytes\n";
        echo self::boxBottom();
    }

    public static function printIPPacket(IPPacket $packet, string $label = 'IP Packet'): void
    {
        $version = $packet->versionAndHeaderLength >> 4;
        $ihl = $packet->versionAndHeaderLength & 0x0F;
        $headerLength = $ihl * 4;

        echo self::boxTop($label);
        echo sprintf('│ Version:        IPv%d%s', $version, PHP_EOL);
        echo "│ Header Length:  {$headerLength} bytes\n";
        echo sprintf('│ Type of Service: %d%s', $packet->typeOfService, PHP_EOL);
        echo "│ Total Length:   {$packet->totalLength} bytes\n";
        echo sprintf('│ Identification: %d%s', $packet->identification, PHP_EOL);
        echo sprintf('│ TTL:            %d%s', $packet->ttl, PHP_EOL);
        echo "│ Protocol:       " . self::getProtocolName($packet->protocol) . " ({$packet->protocol})\n";
        echo "│ Checksum:       0x" . sprintf('%04x', $packet->checksum) . "\n";
        echo "│ Source IP:      " . long2ip($packet->sourceIp) . "\n";
        echo "│ Destination IP: " . long2ip($packet->destinationIp) . "\n";
        echo "│ Payload:        " . strlen($packet->data) . " bytes\n";
        echo self::boxBottom();
    }

    public static function printResponseStats(PacketResponse $response): void
    {
        echo self::boxTop('Response Statistics');

        if ($response->responseTimeMs !== null) {
            echo sprintf("│ Response Time: %.2f ms\n", $response->responseTimeMs);
        }

        if ($response->sourceIp !== null) {
            echo '│ Source:        ' . $response->sourceIp;
            if ($response->sourcePort > 0) {
                echo ':' . $response->sourcePort;
            }

            echo "\n";
        }

        echo sprintf('│ Bytes Sent:    %d%s', $response->bytesSent, PHP_EOL);
        echo sprintf('│ Bytes Received: %d%s', $response->bytesReceived, PHP_EOL);

        echo self::boxBottom();
    }

    public static function printSection(string $title): void
    {
        echo "\n" . str_repeat('═', 80) . "\n";
        echo sprintf('  %s%s', $title, PHP_EOL);
        echo str_repeat('═', 80) . "\n";
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
        echo self::boxTop('ERROR');
        echo sprintf('│ %s%s', $message, PHP_EOL);
        echo self::boxBottom();
    }

    public static function printSuccess(string $message): void
    {
        echo self::boxTop('SUCCESS');
        echo sprintf('│ %s%s', $message, PHP_EOL);
        echo self::boxBottom();
    }

    /**
     * Top border of a box, 81 characters wide: "┌─ Title ───…┐"
     */
    private static function boxTop(string $title): string
    {
        return "\n┌─ {$title} " . str_repeat('─', max(0, 76 - mb_strlen($title))) . "┐\n";
    }

    private static function boxBottom(): string
    {
        return "└" . str_repeat('─', 79) . "┘\n";
    }
}
