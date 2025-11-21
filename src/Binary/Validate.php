<?php

namespace PrettyPhp\Binary;

use Attribute;
use Exception;

/**
 * Attribute for validating field values after unpacking.
 *
 * Example:
 *   #[Validate(min: 0, max: 255)]
 *   #[Binary('8')]
 *   public int $type;
 *
 *   #[Validate(in: [1, 2, 3, 4])]
 *   #[Binary('8')]
 *   public int $version;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validate
{
    public function __construct(
        public ?int $min = null,           // Minimum value (inclusive)
        public ?int $max = null,           // Maximum value (inclusive)
        /** @var array<int|string>|null */
        public ?array $in = null,          // Array of allowed values
        /** @var array<int|string>|null */
        public ?array $notIn = null,       // Array of disallowed values
        public ?string $regex = null,      // Regular expression pattern for strings
        public ?string $message = null     // Custom error message
    ) {
    }

    /**
     * Validate a value against the defined constraints
     *
     * @throws Exception if validation fails
     */
    public function validate(string $propertyName, mixed $value): void
    {
        $errors = [];

        // Min/max validation (for numeric values)
        if ($this->min !== null && is_numeric($value) && $value < $this->min) {
            $errors[] = sprintf("Value %s is less than minimum %d", (string) $value, $this->min);
        }

        if ($this->max !== null && is_numeric($value) && $value > $this->max) {
            $errors[] = sprintf("Value %s is greater than maximum %d", (string) $value, $this->max);
        }

        // In/not-in validation
        if ($this->in !== null && !in_array($value, $this->in, true)) {
            $valueStr = is_scalar($value) ? (string) $value : var_export($value, true);
            $errors[] = sprintf("Value %s is not in allowed set", $valueStr);
        }

        if ($this->notIn !== null && in_array($value, $this->notIn, true)) {
            $errors[] = sprintf("Value %s is in disallowed set", (string) $value);
        }

        // Regex validation (for string values)
        if ($this->regex !== null && is_string($value) && preg_match($this->regex, $value) === 0) {
            $errors[] = sprintf("Value '%s' does not match pattern '%s'", $value, $this->regex);
        }

        if ($errors !== []) {
            $message = $this->message ?? sprintf(
                "Validation failed for property '%s': %s",
                $propertyName,
                implode('; ', $errors)
            );
            throw new Exception($message);
        }
    }
}
