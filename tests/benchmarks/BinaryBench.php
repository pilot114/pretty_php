<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\TCPPacket;

#[BeforeMethods('setUp')]
class BinaryBench
{
    private TCPPacket $packet;

    private string $packed;

    public function setUp(): void
    {
        $this->packet = new TCPPacket(sourcePort: 443, destinationPort: 51234, sequenceNumber: 1, data: 'payload');
        $this->packed = Binary::pack($this->packet);
    }

    // ==================== Packing ====================

    #[Revs(2000)]
    #[Iterations(10)]
    public function benchPack(): void
    {
        (void) Binary::pack($this->packet);
    }

    #[Revs(2000)]
    #[Iterations(10)]
    public function benchNativePack(): void
    {
        pack('nnNNnnnnA*', 443, 51234, 1, 0, 0x5000, 65535, 0, 0, 'payload');
    }

    // ==================== Unpacking ====================

    #[Revs(2000)]
    #[Iterations(10)]
    public function benchUnpack(): void
    {
        (void) Binary::unpack($this->packed, TCPPacket::class);
    }

    #[Revs(2000)]
    #[Iterations(10)]
    public function benchNativeUnpack(): void
    {
        unpack('nsrc/ndst/Nseq/Nack/nflags/nwindow/nchecksum/nurgent/A*data', $this->packed);
    }
}
