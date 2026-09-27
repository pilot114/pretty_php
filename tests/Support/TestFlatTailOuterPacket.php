<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

/**
 * Non-flat structure (nested field) that reads after a flat nested structure
 */
class TestFlatTailOuterPacket
{
    #[Binary(TestFlatTailPacket::class)]
    public TestFlatTailPacket $inner;

    #[Binary('A*')]
    public string $rest = '';
}
