<?php

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestNestedPacket
{
    public function __construct(
        #[Binary(TestInnerPacket::class)]
        public TestInnerPacket $inner,
        #[Binary('8')]
        public int $outerValue = 42,
    ) {
    }
}
