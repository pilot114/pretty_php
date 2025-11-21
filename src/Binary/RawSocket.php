<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use RuntimeException;

// Define IP constants if not defined
if (!defined('IP_TTL')) {
    define('IP_TTL', 2);
}
if (!defined('IP_TOS')) {
    define('IP_TOS', 1);
}
if (!defined('IP_RECVTTL')) {
    define('IP_RECVTTL', 12);
}
if (!defined('IP_RECVTOS')) {
    define('IP_RECVTOS', 13);
}

/**
 * RawSocket - Enhanced raw socket handling
 *
 * Provides enhanced functionality for working with raw sockets,
 * including support for custom IP headers and packet manipulation.
 */
class RawSocket extends Socket
{
    private bool $includeIpHeader = false;

    /**
     * Create a raw socket for ICMP
     */
    public static function icmp(): self
    {
        return new self(AF_INET, SOCK_RAW, 1); // ICMP protocol is 1
    }

    /**
     * Create a raw socket for TCP
     */
    public static function tcp(): self
    {
        return new self(AF_INET, SOCK_RAW, 6); // TCP protocol is 6
    }

    /**
     * Create a raw socket for UDP
     */
    public static function udp(): self
    {
        return new self(AF_INET, SOCK_RAW, 17); // UDP protocol is 17
    }

    /**
     * Create a raw socket for a custom protocol
     */
    public static function protocol(int $protocol): self
    {
        return new self(AF_INET, SOCK_RAW, $protocol);
    }

    /**
     * Enable IP header inclusion (IP_HDRINCL)
     *
     * When enabled, the application must construct the entire IP header.
     * The kernel will not add its own IP header.
     */
    public function enableIpHeaderInclude(): self
    {
        if (!defined('IP_HDRINCL')) {
            define('IP_HDRINCL', 2);
        }

        $this->setOption(IPPROTO_IP, IP_HDRINCL, 1);
        $this->includeIpHeader = true;

        return $this;
    }

    /**
     * Disable IP header inclusion
     */
    public function disableIpHeaderInclude(): self
    {
        if (!defined('IP_HDRINCL')) {
            define('IP_HDRINCL', 2);
        }

        $this->setOption(IPPROTO_IP, IP_HDRINCL, 0);
        $this->includeIpHeader = false;

        return $this;
    }

    /**
     * Check if IP header inclusion is enabled
     */
    public function isIpHeaderIncluded(): bool
    {
        return $this->includeIpHeader;
    }

    /**
     * Send a packet (automatically serialized from an object)
     */
    public function sendPacket(object $packet, string $destination, int $port = 0): PacketResponse
    {
        $startTime = microtime(true);

        // Serialize the packet
        $data = Binary::pack($packet);

        // Send the packet
        $bytesSent = $this->sendTo($data, $destination, $port);

        return new PacketResponse(
            requestData: $data,
            responseData: null,
            sourceIp: null,
            sourcePort: 0,
            bytesSent: $bytesSent,
            bytesReceived: 0,
            responseTimeMs: (microtime(true) - $startTime) * 1000,
        );
    }

    /**
     * Send a packet and wait for a response
     *
     * @template T of object
     * @param class-string<T>|null $responseClass
     */
    public function sendAndReceive(
        object $packet,
        string $destination,
        int $port = 0,
        ?string $responseClass = null,
        int $bufferSize = 65535,
        int $timeoutSeconds = 1
    ): PacketResponse {
        // Set receive timeout
        $this->setReceiveTimeout($timeoutSeconds);

        $startTime = microtime(true);

        // Serialize and send the packet
        $data = Binary::pack($packet);
        $bytesSent = $this->sendTo($data, $destination, $port);

        // Receive response
        try {
            $response = $this->receiveFrom($bufferSize);
            $responseTime = (microtime(true) - $startTime) * 1000;

            return new PacketResponse(
                requestData: $data,
                responseData: $response['data'],
                sourceIp: $response['address'],
                sourcePort: $response['port'],
                bytesSent: $bytesSent,
                bytesReceived: strlen($response['data']),
                responseTimeMs: $responseTime,
            );
        } catch (RuntimeException $e) {
            // Timeout or error
            return new PacketResponse(
                requestData: $data,
                responseData: null,
                sourceIp: null,
                sourcePort: 0,
                bytesSent: $bytesSent,
                bytesReceived: 0,
                responseTimeMs: (microtime(true) - $startTime) * 1000,
            );
        }
    }

    /**
     * Receive a packet and optionally deserialize it
     *
     * @template T of object
     * @param class-string<T>|null $packetClass
     * @return array{data: string, address: string, port: int, packet?: T}
     */
    public function receivePacket(?string $packetClass = null, int $bufferSize = 65535): array
    {
        $result = $this->receiveFrom($bufferSize);

        if ($packetClass !== null && $result['data'] !== '') {
            $result['packet'] = Binary::unpack($result['data'], $packetClass);
        }

        return $result;
    }

    /**
     * Enable promiscuous mode (receive all packets on the network)
     *
     * Note: This may require root privileges and may not work on all systems
     */
    public function enablePromiscuousMode(): self
    {
        // Promiscuous mode is typically set at the interface level
        // This is a placeholder for future implementation
        throw new RuntimeException('Promiscuous mode is not yet implemented');
    }

    /**
     * Set IP options for the socket
     *
     * @param array<string, mixed> $options
     */
    public function setIpOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            $optionCode = match ($option) {
                'ttl' => IP_TTL,
                'tos' => IP_TOS,
                'recvttl' => IP_RECVTTL,
                'recvtos' => IP_RECVTOS,
                default => throw new RuntimeException("Unknown IP option: {$option}"),
            };

            $this->setOption(IPPROTO_IP, $optionCode, $value);
        }

        return $this;
    }

    /**
     * Set the Time-To-Live (TTL) for outgoing packets
     */
    public function setTTL(int $ttl): self
    {
        $this->setOption(IPPROTO_IP, IP_TTL, $ttl);
        return $this;
    }

    /**
     * Set the Type of Service (TOS) for outgoing packets
     */
    public function setTOS(int $tos): self
    {
        $this->setOption(IPPROTO_IP, IP_TOS, $tos);
        return $this;
    }

    /**
     * Bind to a specific network interface
     */
    public function bindToInterface(string $interfaceName): self
    {
        if (!defined('SO_BINDTODEVICE')) {
            define('SO_BINDTODEVICE', 25);
        }

        $this->setOption(SOL_SOCKET, SO_BINDTODEVICE, $interfaceName);
        return $this;
    }

    /**
     * Enable broadcast on the socket
     */
    public function enableBroadcast(): self
    {
        $this->setOption(SOL_SOCKET, SO_BROADCAST, 1);
        return $this;
    }

    /**
     * Disable broadcast on the socket
     */
    public function disableBroadcast(): self
    {
        $this->setOption(SOL_SOCKET, SO_BROADCAST, 0);
        return $this;
    }
}
