<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use RuntimeException;
use Socket as PhpSocket;
use PrettyPhp\Binary\Security\RateLimiter;

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

    private ?RateLimiter $rateLimiter = null;

    /**
     * Create a new socket
     *
     * @param int $domain AF_INET, AF_INET6, AF_UNIX
     * @param int $type SOCK_STREAM, SOCK_DGRAM, SOCK_RAW
     * @param int $protocol SOL_TCP, SOL_UDP, or protocol number
     * @throws RuntimeException
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
     * @throws RuntimeException
     */
    public static function tcp(): self
    {
        return new self(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    /**
     * Create a UDP socket
     * @throws RuntimeException
     */
    public static function udp(): self
    {
        return new self(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * Create a raw socket for the specified protocol
     *
     * @param int $protocol Protocol number (e.g., 1 for ICMP)
     * @throws RuntimeException
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
     * @throws RuntimeException
     */
    public function bind(string $address, int $port = 0): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        if (socket_bind($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to bind socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Connect the socket to a remote address
     * @throws RuntimeException
     */
    public function connect(string $address, int $port): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        if (socket_connect($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to connect socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Listen for incoming connections
     * @throws RuntimeException
     */
    public function listen(int $backlog = 0): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        if (socket_listen($this->socket, $backlog) === false) {
            throw new RuntimeException(
                'Failed to listen on socket: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Accept an incoming connection
     * @throws RuntimeException
     */
    public function accept(): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        $clientSocket = socket_accept($this->socket);
        if ($clientSocket === false) {
            throw new RuntimeException(
                'Failed to accept connection: ' . $this->getLastError()
            );
        }

        // Create a new Socket instance wrapping the client socket
        $socket = new self($this->domain, $this->type, $this->protocol);
        if ($socket->socket instanceof PhpSocket) {
            socket_close($socket->socket);
        }

        $socket->socket = $clientSocket;

        return $socket;
    }

    /**
     * Set rate limiter for this socket
     */
    public function setRateLimiter(?RateLimiter $rateLimiter): self
    {
        $this->rateLimiter = $rateLimiter;
        return $this;
    }

    /**
     * Get rate limiter for this socket
     */
    public function getRateLimiter(): ?RateLimiter
    {
        return $this->rateLimiter;
    }

    /**
     * Send data through the socket
     * @throws RuntimeException
     */
    public function send(string $data, int $flags = 0): int
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        // Apply rate limiting if configured
        if ($this->rateLimiter instanceof \PrettyPhp\Binary\Security\RateLimiter) {
            $this->rateLimiter->checkLimit('socket send');
        }

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
     * @throws RuntimeException
     */
    public function sendTo(string $data, string $address, int $port, int $flags = 0): int
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        // Apply rate limiting if configured
        if ($this->rateLimiter instanceof \PrettyPhp\Binary\Security\RateLimiter) {
            $this->rateLimiter->checkLimit('socket sendTo');
        }

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
     * @throws RuntimeException
     */
    public function receive(int $length, int $flags = 0): string
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        // Apply rate limiting if configured
        if ($this->rateLimiter instanceof \PrettyPhp\Binary\Security\RateLimiter) {
            $this->rateLimiter->checkLimit('socket receive');
        }

        $buffer = '';
        $result = socket_recv($this->socket, $buffer, $length, $flags);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to receive data: ' . $this->getLastError()
            );
        }

        assert(is_string($buffer));
        return $buffer;
    }

    /**
     * Receive data from a specific address (for connectionless sockets)
     *
     * @return array{data: string, address: string, port: int}
     * @throws RuntimeException
     */
    public function receiveFrom(int $length, int $flags = 0): array
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        // Apply rate limiting if configured
        if ($this->rateLimiter instanceof \PrettyPhp\Binary\Security\RateLimiter) {
            $this->rateLimiter->checkLimit('socket receiveFrom');
        }

        $buffer = '';
        $address = '';
        $port = 0;

        $result = socket_recvfrom($this->socket, $buffer, $length, $flags, $address, $port);
        if ($result === false) {
            throw new RuntimeException(
                'Failed to receive data: ' . $this->getLastError()
            );
        }

        assert(is_string($buffer));
        assert(is_string($address));
        assert(is_int($port));

        return [
            'data' => $buffer,
            'address' => $address,
            'port' => $port,
        ];
    }

    /**
     * Set socket option
     * @throws RuntimeException
     */
    public function setOption(int $level, int $option, mixed $value): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        // Ensure $value is valid for socket_set_option
        if (!is_int($value) && !is_string($value) && !is_array($value)) {
            throw new RuntimeException('Invalid socket option value type');
        }

        if (socket_set_option($this->socket, $level, $option, $value) === false) {
            throw new RuntimeException(
                'Failed to set socket option: ' . $this->getLastError()
            );
        }

        return $this;
    }

    /**
     * Get socket option
     * @throws RuntimeException
     */
    public function getOption(int $level, int $option): mixed
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

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
     * @throws RuntimeException
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
     * @throws RuntimeException
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
     * @throws RuntimeException
     */
    public function setBlocking(bool $blocking): self
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

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
     * @throws RuntimeException
     */
    public function getName(): array
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        $address = '';
        $port = 0;

        if (socket_getsockname($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to get socket name: ' . $this->getLastError()
            );
        }

        assert(is_string($address));
        assert(is_int($port));

        return [
            'address' => $address,
            'port' => $port,
        ];
    }

    /**
     * Get peer name (remote address and port)
     *
     * @return array{address: string, port: int}
     * @throws RuntimeException
     */
    public function getPeerName(): array
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);

        $address = '';
        $port = 0;

        if (socket_getpeername($this->socket, $address, $port) === false) {
            throw new RuntimeException(
                'Failed to get peer name: ' . $this->getLastError()
            );
        }

        assert(is_string($address));
        assert(is_int($port));

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
        if (!$this->closed && $this->socket instanceof \Socket) {
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
     * @throws RuntimeException
     */
    public function getResource(): PhpSocket
    {
        $this->ensureOpen();
        assert($this->socket instanceof PhpSocket);
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
     * @throws RuntimeException
     */
    private function ensureOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('Socket is closed');
        }

        if (!$this->socket instanceof \Socket) {
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
