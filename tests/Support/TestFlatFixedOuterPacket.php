<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

/**
 * Non-flat structure (nested field) with a field after a fixed-size flat nested structure
 */
class TestFlatFixedOuterPacket
{
    public function __construct(
        #[Binary(TestInnerPacket::class)]
        public TestInnerPacket $inner,
        #[Binary('n')]
        public int $after = 0,
    ) {
    }
}
