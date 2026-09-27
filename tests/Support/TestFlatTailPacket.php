<?php

declare(strict_types=1);

namespace Tests\Support;

use PrettyPhp\Binary\Binary;

/**
 * Flat structure with a variable-length tail, nested in TestFlatTailOuterPacket
 */
class TestFlatTailPacket
{
    #[Binary('C')]
    public int $kind = 0;

    #[Binary('A*')]
    public string $payload = '';
}
