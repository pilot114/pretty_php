<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

/**
 * Parent of a binary structure: its property is last in the schema but first in the (array) cast
 */
class TestFlatBasePacket
{
    #[Binary('C')]
    public int $inherited = 0x11;
}
