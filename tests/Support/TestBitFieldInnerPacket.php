<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;

class TestBitFieldInnerPacket
{
    public function __construct(
        #[BitField(bits: 4)]
        public int $flags = 0,
        #[Binary('8')]
        public int $value = 0,
    ) {
    }
}
