<?php

declare(strict_types=1);

namespace PrettyPhp\Exception;

use RuntimeException;

/**
 * Base exception for all Pretty PHP errors.
 * Extends RuntimeException for backward compatibility.
 */
class PrettyPhpException extends RuntimeException
{
}
