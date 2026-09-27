<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use ReflectionClass;
use ReflectionProperty;

/**
 * Precompiled layout of a flat binary structure: only regular fixed-size fields, optionally followed by
 * one variable-length `A*` field, without bit fields, conditions or nested structures.
 *
 * Such a structure is packed with one pack() call over the `(array)` cast of the object and unpacked with
 * one named unpack() call.
 *
 * @internal
 */
final readonly class FlatLayout
{
    /**
     * @param string $packFormat Concatenated pack() format, e.g. "nnNA*"
     * @param string $unpackFormat Named unpack() format, e.g. "nsourcePort/ndestinationPort/NsequenceNumber/A*data"
     * @param int $fixedSize Total size of the fixed-size fields in bytes
     * @param string|null $variableField Name of the trailing variable-length `A*` field, if any
     * @param array<string, ReflectionProperty> $properties Properties by name, in declaration order
     * @param list<array{string, list<Validate>}> $validators Validators per property name, in declaration order
     */
    private function __construct(
        public string $packFormat,
        public string $unpackFormat,
        public int $fixedSize,
        public ?string $variableField,
        public array $properties,
        public array $validators,
    ) {
    }

    /**
     * Build the layout, or return null when the structure needs the generic field-by-field path
     *
     * @param ReflectionClass<object> $class
     * @param list<BinaryField> $fields
     */
    public static function tryCreate(ReflectionClass $class, array $fields): ?self
    {
        // Inherited properties come first in the (array) cast of an object but last in the schema
        if ($class->getParentClass() instanceof ReflectionClass) {
            return null;
        }

        $packFormat = '';
        $unpackFormat = [];
        $fixedSize = 0;
        $variableField = null;
        $properties = [];
        $validators = [];

        foreach ($fields as $field) {
            $format = $field->packFormat;
            if ($variableField !== null || $format === null || $field->conditional instanceof Conditional) {
                return null;
            }

            $name = $field->property->getName();
            if ($field->size === null) {
                // Only a trailing A* has a variable size that the generic path supports
                if ($format !== 'A*') {
                    return null;
                }

                $variableField = $name;
            }

            $properties[$name] = $field->property;
            $packFormat .= $format;
            $unpackFormat[] = $format . $name;
            $fixedSize += $field->size ?? 0;
            if ($field->validators !== []) {
                $validators[] = [$name, $field->validators];
            }
        }

        return new self(
            $packFormat,
            implode('/', $unpackFormat),
            $fixedSize,
            $variableField,
            $properties,
            $validators,
        );
    }
}
