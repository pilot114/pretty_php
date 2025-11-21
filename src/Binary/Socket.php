<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use RuntimeException;
use Socket as PhpSocket;

/**
 * Socket - Wrapper for socket operations
 *
 * Provides an object-oriented wrapper around PHP's socket functions with
 * proper error handling and a fluent interface.
 */
class Socket
{
    private ?PhpSocket $socket = null;
    private bool $closed = false;

    /**
     * Create a new socket
     *
     * @param int $domain AF_INET, AF_INET6, AF_UNIX
     * @param int $type SOCK_STREAM, SOCK_DGRAM, SOCK_RAW
     * @param int $protocol SOL_TCP, SOL_UDP, or protocol number
     */
    public function __construct(
        private readonly int $domain,
        private readonly int $type,
        private readonly int $protocol
    ) {
        $socket = socket_create($domain, $type, $protocol);
        if ($socket === false) {
            throw new RuntimeException(
                'Failed to create socket: ' . socket_strerror(socket_last_error())
            );
        }
        $this->socket = $socket;
    }

    /**
     * Create a TCP socket
     */
    public static function tcp(): self
    {
        return new self(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    /**
     * Create a UDP socket
     */
    public static function udp(): self
    {
        return new self(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * Create a raw socket for the specified protocol
     *
     * @param int $protocol Protocol number (e.g., 1 for ICMP)
     */
    public static function raw(int $protocol): self
    {
        // Check privileges for raw socket
        if (posix_geteuid() !== 0) {
            throw new RuntimeException(
                'Raw sockets require superuser (root) privileges'
            );
        }

        return new self(AF_INET, SOCK_RAW, $protocol);
    }

    /**
     * Bind the socket to an address and port
     */
    public function bind(string $address, int $port = 0): self
    {
        $this->ensureOpen();

        if (socket_bind($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to bind socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Connect the socket to a remote address
     */
    public function connect(string $address, int $port): self
    {
        $this->ensureOpen();

        if (socket_connect($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to connect socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Listen for incoming connections
     */
    public function listen(int $backlog = 0): self
    {
        $this->ensureOpen();

        if (socket_listen($this->socket, $backlog) === false) {
            throw new RuntimeException(
                'Failed to listen on socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Accept an incoming connection
     */
    public function accept(): self
    {
        $this->ensureOpen();

        $clientSocket = socket_accept($this->socket);
        if ($clientSocket === false) {
            throw new RuntimeException(
                'Failed to accept connection: ' . $this->getLastError()
            );
        }

        // Create a new Socket instance wrapping the client socket
        $socket = new self($this->domain, $this->type, $this->protocol);
        socket_close($socket->socket);
        $socket->socket = $clientSocket;

        return $socket;
    }

    /**
     * Send data through the socket
     */
    public function send(string $data, int $flags = 0): int
    {
        $this->ensureOpen();

        $result = socket_send($this->socket, $data, strlen($data), $flags);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to send data: ' . $this->getLastError()
            );
        }

        return $result;
    }

    /**
     * Send data to a specific address (for connectionless sockets)
     */
    public function sendTo(string $data, string $address, int $port, int $flags = 0): int
    {
        $this->ensureOpen();

        $result = socket_sendto($this->socket, $data, strlen($data), $flags, $address, $port);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to send data: ' . $this->getLastError()
            );
        }

        return $result;
    }

    /**
     * Receive data from the socket
     */
    public function receive(int $length, int $flags = 0): string
    {
        $this->ensureOpen();

        $buffer = '';
        $result = socket_recv($this->socket, $buffer, $length, $flags);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to receive data: ' . $this->getLastError()
            );
        }

        return $buffer;
    }

    /**
     * Receive data from a specific address (for connectionless sockets)
     *
     * @return array{data: string, address: string, port: int}
     */
    public function receiveFrom(int $length, int $flags = 0): array
    {
        $this->ensureOpen();

        $buffer = '';
        $address = '';
        $port = 0;

        $result = socket_recvfrom($this->socket, $buffer, $length, $flags, $address, $port);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to receive data: ' . $this->getLastError()
            );
        }

        return [
            'data' => $buffer,
            'address' => $address,
            'port' => $port,
        ];
    }

    /**
     * Set socket option
     *
     * @param mixed $value
     */
    public function setOption(int $level, int $option, mixed $value): self
    {
        $this->ensureOpen();

        if (socket_set_option($this->socket, $level, $option, $value) === false) {
            throw new RuntimeException(
                'Failed to set socket option: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Get socket option
     *
     * @return mixed
     */
    public function getOption(int $level, int $option): mixed
    {
        $this->ensureOpen();

        $result = socket_get_option($this->socket, $level, $option);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to get socket option: ' . $this->getLastError()
            );
        }

        return $result;
    }

    /**
     * Set receive timeout
     */
    public function setReceiveTimeout(int $seconds, int $microseconds = 0): self
    {
        return $this->setOption(SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds,
        ]);
    }

    /**
     * Set send timeout
     */
    public function setSendTimeout(int $seconds, int $microseconds = 0): self
    {
        return $this->setOption(SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => $seconds,
            'usec' => $microseconds,
        ]);
    }

    /**
     * Enable or disable blocking mode
     */
    public function setBlocking(bool $blocking): self
    {
        $this->ensureOpen();

        $result = $blocking
            ? socket_set_block($this->socket)
            : socket_set_nonblock($this->socket);

        if ($result === false) {
            throw new RuntimeException(
                'Failed to set blocking mode: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Get socket name (local address and port)
     *
     * @return array{address: string, port: int}
     */
    public function getName(): array
    {
        $this->ensureOpen();

        $address = '';
        $port = 0;

        if (socket_getsockname($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to get socket name: ' . $this->getLastError()
            );
        }

        return [
            'address' => $address,
            'port' => $port,
        ];
    }

    /**
     * Get peer name (remote address and port)
     *
     * @return array{address: string, port: int}
     */
    public function getPeerName(): array
    {
        $this->ensureOpen();

        $address = '';
        $port = 0;

        if (socket_getpeername($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to get peer name: ' . $this->getLastError()
            );
        }

        return [
            'address' => $address,
            'port' => $port,
        ];
    }

    /**
     * Close the socket
     */
    public function close(): void
    {
        if (!$this->closed && $this->socket !== null) {
            socket_close($this->socket);
            $this->closed = true;
        }
    }

    /**
     * Check if the socket is closed
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Get the underlying PHP socket resource
     */
    public function getResource(): PhpSocket
    {
        $this->ensureOpen();
        return $this->socket;
    }

    /**
     * Get the last socket error message
     */
    private function getLastError(): string
    {
        return socket_strerror(socket_last_error($this->socket));
    }

    /**
     * Ensure the socket is open
     */
    private function ensureOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('Socket is closed');
        }

        if ($this->socket === null) {
            throw new RuntimeException('Socket is not initialized');
        }
    }

    /**
     * Close the socket when the object is destroyed
     */
    public function __destruct()
    {
        $this->close();
    }
}
