<?php

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestInnerPacket
{
    public function __construct(
        #[Binary('8')]
        public int $innerValue = 99,
    ) {
    }
}
