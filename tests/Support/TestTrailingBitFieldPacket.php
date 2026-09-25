<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;

class TestTrailingBitFieldPacket
{
    public function __construct(
        #[Binary('8')]
        public int $value = 0,
        #[BitField(bits: 4)]
        public int $low = 0,
        #[BitField(bits: 4, offset: 4)]
        public int $high = 0,
    ) {
    }
}
