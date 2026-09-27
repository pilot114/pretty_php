<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use Attribute;

/**
 * Attribute for conditional field packing/unpacking.
 *
 * Fields with this attribute will only be packed/unpacked if the condition is met.
 *
 * Example:
 *   #[Conditional(field: 'type', operator: '==', value: 1)]
 *   #[Binary('16')]
 *   public int $optionalField;
 *
 * This field will only be included if $this->type == 1
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Conditional
{
    public function __construct(
        public string $field,           // Field name to check
        public string $operator = '==', // Comparison operator (==, !=, <, >, <=, >=)
        public mixed $value = null      // Value to compare against
    ) {
    }

    /**
     * Evaluate if the condition is met
     */
    public function evaluate(object $object): bool
    {
        $reflectionClass = new \ReflectionClass($object);

        if (!$reflectionClass->hasProperty($this->field)) {
            return false;
        }

        return $this->matches($reflectionClass->getProperty($this->field)->getValue($object));
    }

    /**
     * Check the condition against an already read field value
     */
    public function matches(mixed $fieldValue): bool
    {
        return match ($this->operator) {
            '==' => $fieldValue === $this->value,
            '!=' => $fieldValue !== $this->value,
            '<' => $fieldValue < $this->value,
            '>' => $fieldValue > $this->value,
            '<=' => $fieldValue <= $this->value,
            '>=' => $fieldValue >= $this->value,
            default => false,
        };
    }
}
