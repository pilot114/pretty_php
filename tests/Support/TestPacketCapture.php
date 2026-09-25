<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\PacketCapture;
use PrettyPhp\Binary\RawSocket;

/**
 * PacketCapture that listens on a local UDP socket instead of a raw socket, so it works without root
 */
class TestPacketCapture extends PacketCapture
{
    public ?RawSocket $createdSocket = null;

    public ?int $requestedProtocol = null;

    protected function checkPrivileges(): void
    {
    }

    protected function createSocket(int $protocol): RawSocket
    {
        $this->requestedProtocol = $protocol;
        $this->createdSocket = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->createdSocket->bind('127.0.0.1');

        return $this->createdSocket;
    }

    public function port(): int
    {
        return $this->createdSocket?->getName()['port'] ?? 0;
    }
}
