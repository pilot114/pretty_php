<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;
use PrettyPhp\Binary\Conditional;
use PrettyPhp\Binary\Validate;

/**
 * Structure exercising every documentation feature: conditions, validators, bit fields, variable length
 */
class TestAnnotatedPacket
{
    public function __construct(
        #[Binary('8')]
        #[Validate(min: 1, max: 9)]
        #[Validate(in: [1, 2])]
        public int $type = 1,
        #[Conditional(field: 'type', operator: '>=', value: 2)]
        #[Binary('16', Binary::ENDIAN_LITTLE)]
        public int $extra = 0,
        #[Conditional(field: 'type', operator: '!=', value: [3])]
        #[Binary('8')]
        public int $guarded = 0,
        #[BitField(bits: 3)]
        public int $mode = 0,
        #[BitField(bits: 7, offset: 3)]
        public int $level = 0,
        #[Binary('A*')]
        public string $payload = '',
        #[Binary('8')]
        public int $afterPayload = 0,
        #[BitField(bits: 2)]
        public int $tailBits = 0,
    ) {
    }
}
