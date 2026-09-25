<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\Conditional;

class TestConditionalInnerPacket
{
    public function __construct(
        #[Binary('8')]
        public int $type = 0,
        #[Conditional(field: 'type', operator: '==', value: 1)]
        #[Binary('16')]
        public int $extra = 0,
    ) {
    }
}
