<?php

declare(strict_types=1);

namespace PrettyPhp\Base;

/**
 * Mutable holder of the decoded value of a JSON string, owned by one immutable Json instance.
 *
 * @internal
 */
final class JsonCache
{
    public bool $isDecoded = false;

    public mixed $decoded = null;
}
