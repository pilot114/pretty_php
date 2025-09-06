<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\Binary;

function sendICMPRequest(string $host, ICMPPacket $packet): array
{
    // Check privileges for raw socket
    if (posix_geteuid() !== 0) {
        die("This script requires superuser (root) privileges to create raw sockets.\n");
    }

    $socket = socket_create(AF_INET, SOCK_RAW, 1); // ICMP protocol is 1
    if ($socket === false) {
        die('Failed to create socket: ' . socket_strerror(socket_last_error()) . "\n");
    }

    try {
        $packetData = Binary::pack($packet);
    } catch (Exception $e) {
        socket_close($socket);
        die('Failed to serialize packet: ' . $e->getMessage() . "\n");
    }

    echo sprintf("%s >>> %s\n", strlen($packetData), bin2hex($packetData));

    // Send ICMP request
    if (socket_sendto($socket, $packetData, strlen($packetData), 0, $host, 0) === false) {
        socket_close($socket);
        die('Error sending data: ' . socket_strerror(socket_last_error()) . "\n");
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
        die('Error receiving data: ' . socket_strerror(socket_last_error()) . "\n");
    }

    socket_close($socket);
    echo sprintf("%s <<< %s\n", strlen($buffer), bin2hex($buffer));

    // TODO: response common format
    return [
        'source_ip' => $sourceAddress,
        'source_port' => $sourcePort,
        'response_time_ms' => (microtime(true) - $startTime) * 1000,
        'ip_header' => substr($buffer, 0, 20),
        'icmp_header' => substr($buffer, 20),
    ];
}

function sendIPPacket(string $host, IPPacket $packet): void
{
    if (!defined('IP_HDRINCL')) {
        define('IP_HDRINCL', 2);
    }

    // Create raw socket for sending IP packet
    $socket = socket_create(AF_INET, SOCK_RAW, SOL_SOCKET);
    if ($socket === false) {
        die('Error creating socket: ' . socket_strerror(socket_last_error()) . "\n");
    }

    // Set IP_HDRINCL flag so system doesn't add its own IP header
    if (socket_set_option($socket, IPPROTO_IP, IP_HDRINCL, 1) === false) {
        socket_close($socket);
        die('Error setting IP_HDRINCL: ' . socket_strerror(socket_last_error()) . "\n");
    }

    try {
        $binaryData = Binary::pack($packet);
    } catch (Exception $e) {
        socket_close($socket);
        die('Failed to serialize packet: ' . $e->getMessage() . "\n");
    }

    // Send packet
    if (socket_sendto($socket, $binaryData, strlen($binaryData), 0, $host, 0) === false) {
        socket_close($socket);
        die('Error sending data: ' . socket_strerror(socket_last_error()) . "\n");
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
        "sec" => 1,
        "usec" => 0,
    ]);

    echo "Packet sent successfully!\n";

    $buffer = '';
    if (socket_recvfrom($socket, $buffer, 1024, 0, $sourceAddress, $sourcePort) !== false) {
        echo "Received response from: $sourceAddress\n";

        // Parse IP header
        $ipHeader = substr($buffer, 0, 20);  // First 20 bytes are IP header
        $ipData = unpack('CversionAndIHL/Ctos/nlength/nid/nflagsAndOffset/Cttl/Cprotocol/nchecksum/NsourceIp/NdestinationIp', $ipHeader);

        if ($ipData !== false) {
            echo "Version: " . ($ipData['versionAndIHL'] >> 4) . "\n";
            echo "TTL: " . $ipData['ttl'] . "\n";
            echo "Protocol: " . $ipData['protocol'] . "\n";
            echo "Source IP: " . long2ip($ipData['sourceIp']) . "\n";
            echo "Destination IP: " . long2ip($ipData['destinationIp']) . "\n";
        }
    } else {
        echo "No response received (timeout)\n";
    }

    // Close socket
    socket_close($socket);
}


$request = new ICMPPacket(
    type: 8,
    code: 0,
    checksum: 0,
    // эта часть - для эхо-запрос/ответа
    identifier: random_int(0, 65535),
    sequenceNumber: 1,
    data: str_repeat("\x00", 32)
);

$result = sendICMPRequest('142.251.39.78', $request);

$response = Binary::unpack($result['icmp_header'], ICMPPacket::class);
var_dump($request);
var_dump($response);


$version = 4;         // IPv4
$ihl = 5;             // Длина заголовка в 32-битных словах (5 слов = 20 байт)
$flags = 2;           // DF (Don't Fragment)
$fragmentOffset = 0;  // Нет фрагментации

$ipPacket = new IPPacket(
    versionAndHeaderLength: ($version << 4) | $ihl,
    typeOfService: 0,                      // Стандартный приоритет
    totalLength: 40,                       // Длина пакета (20 байт заголовка + 20 байт данных)
    identification: random_int(0, 65535),  // Уникальный идентификатор пакета
    flagsAndFragmentOffset: ($flags << 13) | $fragmentOffset,
    ttl: 256,  // Стандартное время жизни
    protocol: 1,  // Протокол ICMP
    checksum: 0,
    sourceIp: (int) ip2long('192.168.1.100'),  // IP-адрес источника (например, локальная сеть)
    destinationIp: (int) ip2long('8.8.8.8'),   // IP-адрес назначения (например, Google DNS)
    data: str_repeat("\x00", 20),
);

sendIPPacket('8.8.8.8', $ipPacket);
