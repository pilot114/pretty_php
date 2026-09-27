<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use ReflectionProperty;

/**
 * Precomputed description of a single property of a binary structure.
 *
 * Built once per class by Binary so that pack()/unpack() do not repeat reflection
 * and attribute instantiation on every call.
 *
 * @internal
 */
final readonly class BinaryField
{
    /**
     * @param class-string|null $nestedClass Class of a nested structure
     * @param string|null $packFormat pack() format code for regular fields
     * @param int|null $size Fixed size in bytes, null for variable length (A*)
     * @param list<Validate> $validators
     */
    public function __construct(
        public ReflectionProperty $property,
        public ?Conditional $conditional,
        public ?ReflectionProperty $conditionProperty,
        public ?BitField $bitField,
        public ?string $nestedClass,
        public ?string $packFormat,
        public ?int $size,
        public array $validators,
    ) {
    }

    /**
     * Whether a conditional field is present in the object. Callers pass `$this->conditional` after checking
     * it, so that unconditional fields skip the call.
     */
    public function isIncluded(Conditional $conditional, object $object): bool
    {
        if (!$this->conditionProperty instanceof ReflectionProperty) {
            return false;
        }

        return $conditional->matches($this->conditionProperty->getValue($object));
    }
}
