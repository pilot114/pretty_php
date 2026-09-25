<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestTrailingBitFieldOuterPacket
{
    public function __construct(
        #[Binary(TestTrailingBitFieldPacket::class)]
        public TestTrailingBitFieldPacket $inner,
        #[Binary('8')]
        public int $tail = 0,
    ) {
    }
}
