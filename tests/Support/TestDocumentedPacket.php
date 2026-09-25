<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;
use PrettyPhp\Binary\Validate;

class TestDocumentedPacket
{
    public string $ignored = 'not packed';

    public function __construct(
        #[Binary('16')]
        public int $small = 0,
        #[Binary('32')]
        #[Validate(in: [1, 2, 3])]
        public int $wide = 1,
        #[BitField(bits: 4)]
        public int $high = 0,
        #[BitField(bits: 4, offset: 4)]
        public int $low = 0,
        #[Binary('64')]
        public int $huge = 0,
        #[Binary('A4')]
        public string $tag = 'abcd',
        #[Binary(TestInnerPacket::class)]
        public ?TestInnerPacket $inner = null,
        #[BitField(bits: 3)]
        public int $trailing = 0,
    ) {
    }
}
