<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use RuntimeException;

/**
 * PacketCapture - Basic packet sniffer
 *
 * Provides functionality to capture network packets from an interface.
 * Requires root/administrator privileges.
 */
class PacketCapture
{
    private ?RawSocket $socket = null;

    private bool $capturing = false;

    /** @var array<callable(CapturedPacket): void> */
    private array $handlers = [];

    /** @var array<string> */
    private array $filters = [];

    private int $capturedPackets = 0;

    private int $droppedPackets = 0;

    /**
     * @param string $interface Network interface to capture from (e.g., 'eth0', 'wlan0')
     * @param int $protocol Protocol to capture (0 = all, or specific protocol number)
     * @param int $bufferSize Buffer size for packet capture
     * @throws RuntimeException
     */
    public function __construct(
        private readonly string $interface = '',
        private readonly int $protocol = 0,
        private readonly int $bufferSize = 65535
    ) {
        // Check privileges
        if (posix_geteuid() !== 0) {
            throw new RuntimeException(
                'Packet capture requires superuser (root) privileges'
            );
        }
    }

    /**
     * Create a packet capture for all protocols
     * @throws RuntimeException
     */
    public static function all(string $interface = ''): self
    {
        return new self($interface, 0);
    }

    /**
     * Create a packet capture for ICMP packets
     * @throws RuntimeException
     */
    public static function icmp(string $interface = ''): self
    {
        return new self($interface, 1);
    }

    /**
     * Create a packet capture for TCP packets
     * @throws RuntimeException
     */
    public static function tcp(string $interface = ''): self
    {
        return new self($interface, 6);
    }

    /**
     * Create a packet capture for UDP packets
     * @throws RuntimeException
     */
    public static function udp(string $interface = ''): self
    {
        return new self($interface, 17);
    }

    /**
     * Start capturing packets
     * @throws RuntimeException
     */
    public function start(): self
    {
        if ($this->capturing) {
            throw new RuntimeException('Packet capture is already running');
        }

        // Create raw socket for packet capture
        // Use ETH_P_ALL (0x0003) to capture all protocols
        $protocol = $this->protocol === 0 ? 0x0003 : $this->protocol;

        $this->socket = RawSocket::protocol($protocol);
        $this->socket->setBlocking(false);

        // Bind to specific interface if specified
        if ($this->interface !== '') {
            $this->socket->bindToInterface($this->interface);
        }

        $this->capturing = true;
        $this->capturedPackets = 0;
        $this->droppedPackets = 0;

        return $this;
    }

    /**
     * Stop capturing packets
     */
    public function stop(): self
    {
        if (!$this->capturing) {
            return $this;
        }

        $this->socket?->close();
        $this->socket = null;
        $this->capturing = false;

        return $this;
    }

    /**
     * Add a packet handler callback
     *
     * @param callable(CapturedPacket): void $handler
     */
    public function onPacket(callable $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * Add a filter for packet capture
     *
     * Filters are simple string patterns that must appear in the packet data.
     * For more complex filtering, use the handler callback.
     */
    public function addFilter(string $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): self
    {
        $this->filters = [];
        return $this;
    }

    /**
     * Capture packets (blocking or non-blocking)
     *
     * @param int $count Number of packets to capture (0 = unlimited)
     * @param float $timeout Timeout in seconds (0 = no timeout)
     * @return array<CapturedPacket>
     * @throws RuntimeException
     */
    public function capture(int $count = 0, float $timeout = 0): array
    {
        if (!$this->capturing) {
            throw new RuntimeException('Packet capture is not started');
        }

        $packets = [];
        $startTime = microtime(true);

        while (true) {
            // Check timeout
            if ($timeout > 0 && (microtime(true) - $startTime) >= $timeout) {
                break;
            }

            // Check count limit
            if ($count > 0 && count($packets) >= $count) {
                break;
            }

            // Try to receive a packet
            try {
                if (!$this->socket instanceof \PrettyPhp\Binary\RawSocket) {
                    break;
                }

                $result = $this->socket->receiveFrom($this->bufferSize);

                // Apply filters
                if (!$this->matchesFilters($result['data'])) {
                    $this->droppedPackets++;
                    continue;
                }

                $packet = new CapturedPacket(
                    data: $result['data'],
                    timestamp: microtime(true),
                    sourceAddress: $result['address'],
                    sourcePort: $result['port'],
                    length: strlen($result['data']),
                    interface: $this->interface,
                );

                $this->capturedPackets++;
                $packets[] = $packet;

                // Call handlers
                foreach ($this->handlers as $handler) {
                    $handler($packet);
                }
            } catch (RuntimeException) {
                // No packet available (non-blocking mode)
                usleep(10000); // Sleep 10ms to avoid busy waiting
            }
        }

        return $packets;
    }

    /**
     * Capture packets with a callback (streaming mode)
     *
     * @param callable(CapturedPacket): bool $callback Return false to stop capturing
     * @param float $timeout Timeout in seconds (0 = no timeout)
     * @throws RuntimeException
     */
    public function captureStream(callable $callback, float $timeout = 0): int
    {
        if (!$this->capturing) {
            throw new RuntimeException('Packet capture is not started');
        }

        $startTime = microtime(true);
        $count = 0;

        while (true) {
            // Check timeout
            if ($timeout > 0 && (microtime(true) - $startTime) >= $timeout) {
                break;
            }

            // Try to receive a packet
            try {
                if (!$this->socket instanceof \PrettyPhp\Binary\RawSocket) {
                    break;
                }

                $result = $this->socket->receiveFrom($this->bufferSize);

                // Apply filters
                if (!$this->matchesFilters($result['data'])) {
                    $this->droppedPackets++;
                    continue;
                }

                $packet = new CapturedPacket(
                    data: $result['data'],
                    timestamp: microtime(true),
                    sourceAddress: $result['address'],
                    sourcePort: $result['port'],
                    length: strlen($result['data']),
                    interface: $this->interface,
                );

                $this->capturedPackets++;
                $count++;

                // Call the callback
                $continue = $callback($packet);
                if ($continue === false) {
                    break;
                }
            } catch (RuntimeException) {
                // No packet available (non-blocking mode)
                usleep(10000); // Sleep 10ms to avoid busy waiting
            }
        }

        return $count;
    }

    /**
     * Get capture statistics
     *
     * @return array{captured: int, dropped: int}
     */
    public function getStats(): array
    {
        return [
            'captured' => $this->capturedPackets,
            'dropped' => $this->droppedPackets,
        ];
    }

    /**
     * Check if currently capturing
     */
    public function isCapturing(): bool
    {
        return $this->capturing;
    }

    /**
     * Check if packet data matches all filters
     */
    private function matchesFilters(string $data): bool
    {
        if ($this->filters === []) {
            return true;
        }

        return array_all($this->filters, fn($filter): bool => str_contains($data, $filter));
    }

    /**
     * Clean up on destruction
     */
    public function __destruct()
    {
        $this->stop();
    }
}
