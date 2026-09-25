<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

class TestDeepPacket
{
    public function __construct(
        #[Binary(TestNestedPacket::class)]
        public TestNestedPacket $nested,
        #[Binary('8')]
        public int $tail = 7,
    ) {
    }
}
