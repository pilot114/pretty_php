<?php

namespace PrettyPhp\Binary;

readonly class PacketResponse
{
    public function __construct(
        public string $requestData,
        public ?string $responseData,
        public ?string $sourceIp,
        public int $sourcePort,
        public int $bytesSent,
        public int $bytesReceived,
        public ?float $responseTimeMs = null,
    ) {
    }

    /**
     * Check if response was received
     */
    public function hasResponse(): bool
    {
        return $this->responseData !== null && $this->responseData !== '';
    }

    /**
     * Get response IP packet if available
     */
    public function getResponseIPPacket(): ?IPPacket
    {
        if ($this->responseData === null || strlen($this->responseData) < 20) {
            return null;
        }

        return Binary::unpack(substr($this->responseData, 0, 20), IPPacket::class);
    }
}
