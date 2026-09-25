<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestConditionalOuterPacket
{
    public function __construct(
        #[Binary(TestConditionalInnerPacket::class)]
        public TestConditionalInnerPacket $inner,
        #[Binary('8')]
        public int $tail = 0,
    ) {
    }
}
