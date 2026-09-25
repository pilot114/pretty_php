<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestBitFieldOuterPacket
{
    public function __construct(
        #[Binary(TestBitFieldInnerPacket::class)]
        public TestBitFieldInnerPacket $inner,
        #[Binary('8')]
        public int $tail = 0,
    ) {
    }
}
