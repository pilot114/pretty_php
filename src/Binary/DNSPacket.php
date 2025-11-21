<?php

namespace PrettyPhp\Binary;

/**
 * DNS (Domain Name System) Packet
 * Based on RFC 1035
 *
 * This is a basic implementation of the DNS header.
 * Questions, answers, authority, and additional sections are stored as raw data.
 */
class DNSPacket
{
    // DNS Header Flags
    public const QR_QUERY = 0;

    public const QR_RESPONSE = 1;

    // DNS Opcodes
    public const OPCODE_QUERY = 0;

    public const OPCODE_IQUERY = 1;

    public const OPCODE_STATUS = 2;

    // DNS Response Codes
    public const RCODE_NO_ERROR = 0;

    public const RCODE_FORMAT_ERROR = 1;

    public const RCODE_SERVER_FAILURE = 2;

    public const RCODE_NAME_ERROR = 3;

    public const RCODE_NOT_IMPLEMENTED = 4;

    public const RCODE_REFUSED = 5;

    public function __construct(
        #[Binary('n')] // 2 bytes for transaction ID
        public int $transactionId,
        #[Binary('n')] // 2 bytes for flags
        public int $flags = 0x0100, // Standard query with recursion desired
        #[Binary('n')] // 2 bytes for question count
        public int $questionCount = 0,
        #[Binary('n')] // 2 bytes for answer record count
        public int $answerCount = 0,
        #[Binary('n')] // 2 bytes for authority record count
        public int $authorityCount = 0,
        #[Binary('n')] // 2 bytes for additional record count
        public int $additionalCount = 0,
        #[Binary('A*')] // Questions, answers, authority, and additional sections (variable)
        public string $data = '',
    ) {
    }

    /**
     * Set DNS header flags
     */
    public function setFlags(
        int $qr = self::QR_QUERY,
        int $opcode = self::OPCODE_QUERY,
        bool $aa = false,  // Authoritative Answer
        bool $tc = false,  // Truncation
        bool $rd = true,   // Recursion Desired
        bool $ra = false,  // Recursion Available
        int $rcode = self::RCODE_NO_ERROR
    ): void {
        $flags = 0;
        $flags |= ($qr & 0x1) << 15;      // QR (1 bit)
        $flags |= ($opcode & 0xF) << 11;  // Opcode (4 bits)
        $flags |= ($aa ? 1 : 0) << 10;    // AA (1 bit)
        $flags |= ($tc ? 1 : 0) << 9;     // TC (1 bit)
        $flags |= ($rd ? 1 : 0) << 8;     // RD (1 bit)
        $flags |= ($ra ? 1 : 0) << 7;     // RA (1 bit)
        // 3 bits reserved (Z)
        $flags |= ($rcode & 0xF);         // RCODE (4 bits)

        $this->flags = $flags;
    }

    /**
     * Get the QR flag (Query/Response)
     */
    public function getQR(): int
    {
        return ($this->flags >> 15) & 0x1;
    }

    /**
     * Get the Opcode
     */
    public function getOpcode(): int
    {
        return ($this->flags >> 11) & 0xF;
    }

    /**
     * Get the Response Code
     */
    public function getRCode(): int
    {
        return $this->flags & 0xF;
    }

    /**
     * Check if Recursion Desired flag is set
     */
    public function isRecursionDesired(): bool
    {
        return (($this->flags >> 8) & 0x1) === 1;
    }

    /**
     * Check if Recursion Available flag is set
     */
    public function isRecursionAvailable(): bool
    {
        return (($this->flags >> 7) & 0x1) === 1;
    }
}
