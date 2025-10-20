<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\PacketPrinter;
use PrettyPhp\Binary\PacketResponse;

/**
 * Send ICMP request and return response
 */
function sendICMPRequest(string $host, ICMPPacket $packet): PacketResponse
{
    // Check privileges for raw socket
    if (posix_geteuid() !== 0) {
        throw new RuntimeException("This script requires superuser (root) privileges to create raw sockets.");
    }

    $socket = socket_create(AF_INET, SOCK_RAW, 1); // ICMP protocol is 1
    if ($socket === false) {
        throw new RuntimeException('Failed to create socket: ' . socket_strerror(socket_last_error()));
    }

    try {
        $packetData = Binary::pack($packet);
    } catch (Exception $e) {
        socket_close($socket);
        throw new RuntimeException('Failed to serialize packet: ' . $e->getMessage());
    }

    // Send ICMP request
    if (socket_sendto($socket, $packetData, strlen($packetData), 0, $host, 0) === false) {
        socket_close($socket);
        throw new RuntimeException('Error sending data: ' . socket_strerror(socket_last_error()));
    }

    // Set timeout for receive
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
        "sec" => 1,
        "usec" => 0,
    ]);

    $startTime = microtime(true);
    $buffer = '';
    if (socket_recvfrom($socket, $buffer, 1024, 0, $sourceAddress, $sourcePort) === false) {
        socket_close($socket);
        throw new RuntimeException('Error receiving data: ' . socket_strerror(socket_last_error()));
    }

    socket_close($socket);

    return new PacketResponse(
        requestData: $packetData,
        responseData: $buffer,
        sourceIp: $sourceAddress,
        sourcePort: $sourcePort,
        bytesSent: strlen($packetData),
        bytesReceived: strlen($buffer),
        responseTimeMs: (microtime(true) - $startTime) * 1000,
    );
}

/**
 * Send IP packet and return response
 */
function sendIPPacket(string $host, IPPacket $packet): PacketResponse
{
    if (!defined('IP_HDRINCL')) {
        define('IP_HDRINCL', 2);
    }

    // Create raw socket for sending IP packet
    $socket = socket_create(AF_INET, SOCK_RAW, SOL_SOCKET);
    if ($socket === false) {
        throw new RuntimeException('Error creating socket: ' . socket_strerror(socket_last_error()));
    }

    // Set IP_HDRINCL flag so system doesn't add its own IP header
    if (socket_set_option($socket, IPPROTO_IP, IP_HDRINCL, 1) === false) {
        socket_close($socket);
        throw new RuntimeException('Error setting IP_HDRINCL: ' . socket_strerror(socket_last_error()));
    }

    try {
        $binaryData = Binary::pack($packet);
    } catch (Exception $e) {
        socket_close($socket);
        throw new RuntimeException('Failed to serialize packet: ' . $e->getMessage());
    }

    // Send packet
    if (socket_sendto($socket, $binaryData, strlen($binaryData), 0, $host, 0) === false) {
        socket_close($socket);
        throw new RuntimeException('Error sending data: ' . socket_strerror(socket_last_error()));
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
        "sec" => 1,
        "usec" => 0,
    ]);

    $buffer = '';
    $sourceAddress = null;
    $sourcePort = 0;

    if (socket_recvfrom($socket, $buffer, 1024, 0, $sourceAddress, $sourcePort) !== false) {
        $responseData = $buffer;
    } else {
        $responseData = null;
    }

    socket_close($socket);

    return new PacketResponse(
        requestData: $binaryData,
        responseData: $responseData,
        sourceIp: $sourceAddress,
        sourcePort: $sourcePort,
        bytesSent: strlen($binaryData),
        bytesReceived: $responseData !== null ? strlen($responseData) : 0,
    );
}


// =============================================================================
// ICMP Ping Example
// =============================================================================

PacketPrinter::printSection('ICMP Echo Request Example');

try {
    $request = new ICMPPacket(
        type: 8,
        code: 0,
        checksum: 0,
        identifier: random_int(0, 65535),
        sequenceNumber: 1,
        data: str_repeat("\x00", 32)
    );

    PacketPrinter::printICMPPacket($request, 'Request Packet');
    PacketPrinter::printTransmission('send', Binary::pack($request), 'ICMP Request');

    $response = sendICMPRequest('142.251.39.78', $request);

    PacketPrinter::printTransmission('receive', $response->responseData, 'ICMP Response');
    PacketPrinter::printResponseStats($response);

    // Parse ICMP response
    $ipHeaderSize = 20;
    $icmpData = substr($response->responseData, $ipHeaderSize);
    $icmpResponse = Binary::unpack($icmpData, ICMPPacket::class);

    PacketPrinter::printICMPPacket($icmpResponse, 'Response Packet');

    // Parse and display IP header from response
    $ipHeader = substr($response->responseData, 0, $ipHeaderSize);
    $ipPacket = Binary::unpack($ipHeader, IPPacket::class);
    PacketPrinter::printIPPacket($ipPacket, 'Response IP Header');

    PacketPrinter::printSuccess('ICMP Echo Request/Reply completed successfully');
} catch (RuntimeException $e) {
    PacketPrinter::printError($e->getMessage());
}

// =============================================================================
// Custom IP Packet Example
// =============================================================================

PacketPrinter::printSection('Custom IP Packet Example');

try {
    $version = 4;
    $ihl = 5;
    $flags = 2;
    $fragmentOffset = 0;

    $ipPacket = new IPPacket(
        versionAndHeaderLength: ($version << 4) | $ihl,
        typeOfService: 0,
        totalLength: 40,
        identification: random_int(0, 65535),
        flagsAndFragmentOffset: ($flags << 13) | $fragmentOffset,
        ttl: 64,
        protocol: 1,
        checksum: 0,
        sourceIp: (int) ip2long('192.168.1.100'),
        destinationIp: (int) ip2long('8.8.8.8'),
        data: str_repeat("\x00", 20),
    );

    PacketPrinter::printIPPacket($ipPacket, 'Custom IP Packet');
    PacketPrinter::printTransmission('send', Binary::pack($ipPacket), 'IP Packet');

    $response = sendIPPacket('8.8.8.8', $ipPacket);

    if ($response->hasResponse()) {
        PacketPrinter::printTransmission('receive', $response->responseData, 'IP Response');
        PacketPrinter::printResponseStats($response);

        // Parse response IP header using DTO method
        $responseIpPacket = $response->getResponseIPPacket();
        if ($responseIpPacket !== null) {
            PacketPrinter::printIPPacket($responseIpPacket, 'Response IP Header');
        }

        PacketPrinter::printSuccess('IP packet sent and response received');
    } else {
        PacketPrinter::printResponseStats($response);
        echo "\n⚠ No response received (timeout)\n";
    }
} catch (RuntimeException $e) {
    PacketPrinter::printError($e->getMessage());
}
