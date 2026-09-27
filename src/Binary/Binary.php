<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

use Attribute;
use ReflectionClass;
use Exception;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\SecurityConfig;
use PrettyPhp\Binary\Security\SecurityException;

// Imported so that the engine compiles them to dedicated opcodes instead of namespaced calls
use function array_key_exists;
use function count;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function strlen;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Binary
{
    public const string ENDIAN_BIG = 'big';

    public const string ENDIAN_LITTLE = 'little';

    public function __construct(
        public string $format,
        public string $endian = self::ENDIAN_BIG
    ) {
    }

    /**
     * Convert bit-based format specification to PHP pack format
     * Examples: "8" -> "C", "16" -> "n"/"v", "32" -> "N"/"V"
     */
    private static function convertBitFormatToPackFormat(string $format, string $endian = self::ENDIAN_BIG): string
    {
        if (is_numeric($format)) {
            $bits = (int) $format;
            return match ($bits) {
                8 => 'C',    // unsigned char (1 byte, no endianness)
                16 => $endian === self::ENDIAN_LITTLE ? 'v' : 'n',   // unsigned short (2 bytes)
                32 => $endian === self::ENDIAN_LITTLE ? 'V' : 'N',   // unsigned long (4 bytes)
                64 => $endian === self::ENDIAN_LITTLE ? 'P' : 'J',   // unsigned long long (8 bytes)
                default => throw new Exception(sprintf("Unsupported bit format: %d bits", $bits)),
            };
        }

        return $format; // Return as-is if not numeric (existing format)
    }

    /**
     * Check if format represents a nested structure (class name)
     */
    private static function isNestedStructure(string $format): bool
    {
        return class_exists($format);
    }

    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classes = [];

    /** @var array<class-string, list<BinaryField>> */
    private static array $schemas = [];

    /** @var array<class-string, FlatLayout|false> */
    private static array $flatLayouts = [];

    /**
     * @param class-string $className
     * @return ReflectionClass<object>
     */
    private static function reflection(string $className): ReflectionClass
    {
        return self::$classes[$className] ??= new ReflectionClass($className);
    }

    /**
     * Get the cached field layout of a binary structure
     *
     * @param class-string $className
     * @return list<BinaryField>
     * @throws Exception
     */
    private static function schema(string $className): array
    {
        if (isset(self::$schemas[$className])) {
            return self::$schemas[$className];
        }

        $reflectionClass = self::reflection($className);
        $fields = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $conditionalAttrs = $property->getAttributes(Conditional::class);
            $conditional = $conditionalAttrs === [] ? null : $conditionalAttrs[0]->newInstance();
            $conditionProperty = null;
            if ($conditional instanceof Conditional && $reflectionClass->hasProperty($conditional->field)) {
                $conditionProperty = $reflectionClass->getProperty($conditional->field);
            }

            $binaryAttrs = $property->getAttributes(Binary::class);
            if ($binaryAttrs === []) {
                $bitFieldAttrs = $property->getAttributes(BitField::class);
                if ($bitFieldAttrs === []) {
                    throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
                }

                $fields[] = new BinaryField(
                    $property,
                    $conditional,
                    $conditionProperty,
                    $bitFieldAttrs[0]->newInstance(),
                    null,
                    null,
                    null,
                    [],
                );
                continue;
            }

            $binaryAttr = $binaryAttrs[0]->newInstance();
            $validators = array_map(
                static fn (\ReflectionAttribute $attribute): Validate => $attribute->newInstance(),
                $property->getAttributes(Validate::class)
            );

            if (self::isNestedStructure($binaryAttr->format)) {
                /** @var class-string $nestedClass */
                $nestedClass = $binaryAttr->format;
                $fields[] = new BinaryField(
                    $property,
                    $conditional,
                    $conditionProperty,
                    null,
                    $nestedClass,
                    null,
                    null,
                    $validators,
                );
                continue;
            }

            $packFormat = self::convertBitFormatToPackFormat($binaryAttr->format, $binaryAttr->endian);
            $fields[] = new BinaryField(
                $property,
                $conditional,
                $conditionProperty,
                null,
                null,
                $packFormat,
                self::knownFormatSize($packFormat),
                $validators,
            );
        }

        return self::$schemas[$className] = $fields;
    }

    /**
     * Build and cache the flat layout of a structure; false when it needs the generic field-by-field path.
     * Hot paths read the cache directly: `self::$flatLayouts[$className] ?? self::flatLayout($className)`.
     *
     * @param class-string $className
     * @throws Exception
     */
    private static function flatLayout(string $className): FlatLayout|false
    {
        return self::$flatLayouts[$className] = FlatLayout::tryCreate(
            self::reflection($className),
            self::schema($className)
        ) ?? false;
    }

    /**
     * Size in bytes of a pack() format, or null when it is variable or unknown
     */
    private static function knownFormatSize(string $packFormat): ?int
    {
        if (preg_match('/^A(\d+)$/', $packFormat, $matches) === 1) {
            return (int) $matches[1];
        }

        return match ($packFormat) {
            'C' => 1,
            'n', 'v' => 2,
            'N', 'V' => 4,
            'J', 'P' => 8,
            default => null,
        };
    }

    /**
     * @throws Exception
     */
    public static function pack(object $object): string
    {
        $layout = self::$flatLayouts[$object::class] ?? self::flatLayout($object::class);
        if ($layout !== false) {
            $values = (array) $object;
            // An uninitialized or dynamic property changes the count: the generic path handles it
            if (count($values) === count($layout->properties)) {
                return pack($layout->packFormat, ...array_values($values));
            }
        }

        return self::packFields($object);
    }

    /**
     * Pack a structure field by field (bit fields, conditions, nested structures)
     *
     * @throws Exception
     */
    private static function packFields(object $object): string
    {
        $binaryData = '';
        // Consecutive regular fields are packed with a single pack() call
        $format = '';
        $values = [];
        $bitFieldBuffer = 0;
        $bitFieldSize = 0;

        foreach (self::schema($object::class) as $field) {
            if ($field->conditional instanceof Conditional && !$field->isIncluded($field->conditional, $object)) {
                continue;
            }

            $value = $field->property->getValue($object);

            if ($field->bitField instanceof BitField) {
                assert(is_int($value));
                $mask = (1 << $field->bitField->bits) - 1;
                $bitFieldBuffer |= ($value & $mask) << $field->bitField->offset;
                $bitFieldSize = max($bitFieldSize, $field->bitField->offset + $field->bitField->bits);
                continue;
            }

            // Flush bit field buffer when moving to a regular field
            if ($bitFieldSize > 0) {
                $bytes = (int) ceil($bitFieldSize / 8);
                for ($i = 0; $i < $bytes; $i++) {
                    $format .= 'C';
                    $values[] = ($bitFieldBuffer >> ($i * 8)) & 0xFF;
                }

                $bitFieldBuffer = 0;
                $bitFieldSize = 0;
            }

            if ($field->nestedClass !== null) {
                assert(is_object($value));
                $binaryData .= pack($format, ...$values);
                $format = '';
                $values = [];

                $binaryData .= self::pack($value);
                continue;
            }

            $format .= $field->packFormat;
            $values[] = $value;
        }

        // Flush any remaining bit field buffer
        if ($bitFieldSize > 0) {
            $bytes = (int) ceil($bitFieldSize / 8);
            for ($i = 0; $i < $bytes; $i++) {
                $format .= 'C';
                $values[] = ($bitFieldBuffer >> ($i * 8)) & 0xFF;
            }
        }

        return $binaryData . pack($format, ...$values);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws BufferOverflowException
     * @throws SecurityException
     * @throws Exception
     */
    public static function unpack(string $binaryData, string $className, int $nestingDepth = 0): object
    {
        // Security: Check buffer size to prevent buffer overflow attacks
        $dataLength = strlen($binaryData);
        $maxBufferSize = SecurityConfig::getMaxBufferSize();
        if ($dataLength > $maxBufferSize) {
            throw new BufferOverflowException($dataLength, $maxBufferSize);
        }

        $offset = 0;

        return self::unpackAt($binaryData, $dataLength, $className, $offset, $nestingDepth);
    }

    /**
     * Unpack a structure starting at $offset and advance $offset past it
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws BufferOverflowException
     * @throws SecurityException
     * @throws Exception
     */
    private static function unpackAt(
        string $binaryData,
        int $dataLength,
        string $className,
        int &$offset,
        int $nestingDepth
    ): object {
        // Security: Check nesting depth to prevent stack overflow attacks
        $maxNestingDepth = SecurityConfig::getMaxNestingDepth();
        if ($nestingDepth > $maxNestingDepth) {
            throw new SecurityException(
                sprintf(
                    'Maximum nesting depth exceeded: %d > %d (possible recursive structure attack)',
                    $nestingDepth,
                    $maxNestingDepth
                )
            );
        }

        /** @var T $object */
        $object = (self::$classes[$className] ?? self::reflection($className))->newInstanceWithoutConstructor();

        $layout = self::$flatLayouts[$className] ?? self::flatLayout($className);
        // Too short data takes the generic path, which reports the first field that does not fit
        if ($layout !== false && $offset + $layout->fixedSize <= $dataLength) {
            $values = unpack($layout->unpackFormat, $binaryData, $offset);
            if (is_array($values)) {
                // setValue() coerces scalars like the generic path does
                foreach ($values as $name => $value) {
                    $layout->properties[$name]->setValue($object, $value);
                }

                $offset += $layout->fixedSize;
                $tail = $layout->variableField === null ? null : $values[$layout->variableField];
                if (is_string($tail)) {
                    $offset += strlen($tail);
                }

                foreach ($layout->validators as [$name, $validators]) {
                    foreach ($validators as $validator) {
                        $validator->validate($name, $values[$name]);
                    }
                }

                return $object;
            }
        }

        $bitFieldBuffer = 0;
        $bitFieldBytesRead = 0;

        foreach (self::schema($className) as $field) {
            if ($field->conditional instanceof Conditional && !$field->isIncluded($field->conditional, $object)) {
                continue;
            }

            if ($field->bitField instanceof BitField) {
                // Read bytes into buffer if needed
                $neededBytes = (int) ceil(($field->bitField->offset + $field->bitField->bits) / 8);
                while ($bitFieldBytesRead < $neededBytes) {
                    if ($offset >= $dataLength) {
                        throw new Exception(
                            sprintf("Failed to read byte for bit field '%s'.", $field->property->getName())
                        );
                    }

                    $bitFieldBuffer |= ord($binaryData[$offset]) << ($bitFieldBytesRead * 8);
                    $bitFieldBytesRead++;
                    $offset++;
                }

                $mask = (1 << $field->bitField->bits) - 1;
                $field->property->setValue($object, ($bitFieldBuffer >> $field->bitField->offset) & $mask);
                continue;
            }

            // Reset bit field buffer when moving to regular field
            $bitFieldBuffer = 0;
            $bitFieldBytesRead = 0;

            if ($field->nestedClass !== null) {
                // Security: incremented nesting depth prevents infinite recursion
                $nested = self::unpackAt($binaryData, $dataLength, $field->nestedClass, $offset, $nestingDepth + 1);
                $field->property->setValue($object, $nested);
                continue;
            }

            $packFormat = $field->packFormat ?? throw new \LogicException('Regular field without pack format');

            // Security: Validate we have enough data before unpacking
            $expectedSize = $field->size ?? self::getFormatSize($packFormat);
            if ($offset + $expectedSize > $dataLength) {
                throw new BufferOverflowException($offset + $expectedSize, $dataLength);
            }

            $unpacked = unpack($packFormat, $binaryData, $offset);

            // Size was validated above, so unpack() always yields the value here
            // @codeCoverageIgnoreStart
            if ($unpacked === false || !array_key_exists(1, $unpacked)) {
                $name = $field->property->getName();
                throw new Exception(sprintf("Failed to unpack binary data for property '%s'.", $name));
            }

            // @codeCoverageIgnoreEnd

            $value = $unpacked[1];
            $offset += $field->size ?? (is_string($value) ? strlen($value) : 0);
            $field->property->setValue($object, $value);

            foreach ($field->validators as $validator) {
                $validator->validate($field->property->getName(), $value);
            }
        }

        return $object;
    }

    /**
     * Size in bytes of a field format; variable-length strings (A*) count as 0
     *
     * @throws Exception For formats with unknown size
     */
    private static function getFormatSize(string $format, string $endian = self::ENDIAN_BIG): int
    {
        $packFormat = self::convertBitFormatToPackFormat($format, $endian);
        if ($packFormat === 'A*') {
            return 0;
        }

        return self::knownFormatSize($packFormat)
            ?? throw new Exception(sprintf("Unknown format size for '%s'.", $packFormat));
    }

    /**
     * Generate documentation for a binary structure
     *
     * @param class-string $className
     */
    public static function generateDocumentation(string $className): string
    {
        $reflectionClass = new ReflectionClass($className);
        $doc = "# Binary Structure: {$className}\n\n";
        $doc .= "| Offset | Size | Field | Type | Endian | Constraints |\n";
        $doc .= "|--------|------|-------|------|--------|-------------|\n";

        $offset = 0;
        $bitFieldGroup = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            // Check for conditional
            $conditional = '';
            $conditionalAttrs = $property->getAttributes(Conditional::class);
            if ($conditionalAttrs !== []) {
                $cond = $conditionalAttrs[0]->newInstance();
                $value = self::describeValue($cond->value);
                $conditional = sprintf(' (if %s %s %s)', $cond->field, $cond->operator, $value);
            }

            // Check for validation (constraints of all validators)
            $constraints = [];
            foreach ($property->getAttributes(Validate::class) as $validateAttr) {
                $val = $validateAttr->newInstance();
                if ($val->min !== null) {
                    $constraints[] = 'min=' . $val->min;
                }

                if ($val->max !== null) {
                    $constraints[] = 'max=' . $val->max;
                }

                if ($val->in !== null) {
                    $constraints[] = "in=[" . implode(',', $val->in) . "]";
                }
            }

            $validation = implode(', ', $constraints);

            // Check for bit field
            $bitFieldAttrs = $property->getAttributes(BitField::class);
            if ($bitFieldAttrs !== []) {
                $bitField = $bitFieldAttrs[0]->newInstance();
                $bitFieldGroup[] = [
                    'name' => $propertyName,
                    'bits' => $bitField->bits,
                    'offset' => $bitField->offset,
                ];
                continue;
            }

            // Flush bit field group if any
            if ($bitFieldGroup !== []) {
                $doc .= self::documentBitFieldGroup($bitFieldGroup, $offset);
                $bitFieldGroup = [];
            }

            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                continue;
            }

            $binaryAttr = $attributes[0]->newInstance();
            $format = $binaryAttr->format;
            $endian = $binaryAttr->endian;

            if (self::isNestedStructure($format)) {
                $doc .= sprintf(
                    "| %d | (nested) | %s | %s | - | %s |\n",
                    $offset,
                    $propertyName . $conditional,
                    $format,
                    $validation
                );
            } else {
                $size = self::getFormatSize($format, $endian);
                $typeDesc = match (true) {
                    is_numeric($format) => $format . '-bit',
                    default => $format,
                };

                $doc .= sprintf(
                    "| %d | %d | %s | %s | %s | %s |\n",
                    $offset,
                    $size,
                    $propertyName . $conditional,
                    $typeDesc,
                    $endian,
                    $validation
                );
                $offset += $size;
            }
        }

        // Flush any remaining bit field group
        if ($bitFieldGroup !== []) {
            $doc .= self::documentBitFieldGroup($bitFieldGroup, $offset);
        }

        return $doc . "\n**Total Size**: ~{$offset} bytes (excluding variable-length fields)\n";
    }

    /**
     * Generate ASCII diagram for a binary structure in RFC style
     *
     * @param class-string $className
     */
    public static function generateAsciiDiagram(string $className): string
    {
        $reflectionClass = new ReflectionClass($className);
        $doc = "Binary Structure: {$className}\n\n";

        // Header with bit positions
        $doc .= " 0                   1                   2                   3\n";
        $doc .= " 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1\n";

        $fields = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            // Check for bit field
            $bitFieldAttrs = $property->getAttributes(BitField::class);
            if ($bitFieldAttrs !== []) {
                $fields[] = [
                    'name' => $propertyName,
                    'bits' => $bitFieldAttrs[0]->newInstance()->bits,
                    'type' => 'bitfield',
                ];
                continue;
            }

            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                continue;
            }

            $binaryAttr = $attributes[0]->newInstance();
            $format = $binaryAttr->format;
            $endian = $binaryAttr->endian;

            if (self::isNestedStructure($format)) {
                $fields[] = [
                    'name' => $propertyName,
                    'bits' => 'nested',
                    'type' => 'nested',
                ];
            } else {
                $size = self::getFormatSize($format, $endian);
                $bits = $size * 8;

                // Handle variable length
                if ($format === 'A*') {
                    $fields[] = [
                        'name' => $propertyName,
                        'bits' => 'variable',
                        'type' => 'variable',
                    ];
                } else {
                    $fields[] = [
                        'name' => $propertyName,
                        'bits' => $bits,
                        'type' => 'fixed',
                    ];
                }
            }
        }

        // Generate diagram
        $currentRow = [];
        $currentBits = 0;

        foreach ($fields as $field) {
            if ($field['type'] === 'nested') {
                // Flush current row
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits);
                    $currentRow = [];
                    $currentBits = 0;
                }

                $doc .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
                $doc .= "|" . str_pad($field['name'] . " (nested structure)", 63, " ", STR_PAD_BOTH) . "|\n";
                continue;
            }

            if ($field['type'] === 'variable') {
                // Flush current row
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits);
                    $currentRow = [];
                    $currentBits = 0;
                }

                $doc .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
                $doc .= "|" . str_pad($field['name'] . " (variable length)", 63, " ", STR_PAD_BOTH) . "|\n";
                continue;
            }

            $fieldBits = (int) $field['bits'];

            // Fields wider than a row (e.g. 64-bit integers, long fixed strings) span several full rows
            if ($fieldBits > 32) {
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits);
                    $currentRow = [];
                    $currentBits = 0;
                }

                $name = $field['name'];
                while ($fieldBits > 32) {
                    $doc .= self::renderAsciiRow([['name' => $name, 'bits' => 32, 'type' => $field['type']]], 32);
                    $name = $field['name'] . ' (cont.)';
                    $fieldBits -= 32;
                }

                $field = ['name' => $name, 'bits' => $fieldBits, 'type' => $field['type']];
            }

            // Check if field fits in current row
            if ($currentBits + $fieldBits > 32) {
                // Render current row and start new one
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits);
                }

                $currentRow = [$field];
                $currentBits = $fieldBits;
            } else {
                $currentRow[] = $field;
                $currentBits += $fieldBits;
            }

            // If current row is exactly 32 bits, render it
            if ($currentBits === 32) {
                $doc .= self::renderAsciiRow($currentRow, $currentBits);
                $currentRow = [];
                $currentBits = 0;
            }
        }

        // Render any remaining fields
        if ($currentRow !== []) {
            $doc .= self::renderAsciiRow($currentRow, $currentBits);
        }

        return $doc . "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
    }

    /**
     * Render a single row of ASCII diagram
     *
     * @param array<array{name: string, bits: int|string, type: string}> $fields
     */
    private static function renderAsciiRow(array $fields, int $totalBits): string
    {
        $row = '';

        $row .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";

        // Calculate padding
        $padding = 32 - $totalBits;

        $row .= "|";
        foreach ($fields as $field) {
            $fieldBits = (int) $field['bits'];
            // Each bit takes 2 characters ("+-"), one of them is used by the field separator
            $width = $fieldBits * 2 - 1;

            $name = $field['name'];
            // Only truncate if name is significantly longer than width
            if (strlen($name) > $width) {
                $name = substr($name, 0, max(1, $width - 1));
            }

            $row .= str_pad($name, $width, " ", STR_PAD_BOTH);
            $row .= "|";
        }

        // Add padding if needed
        if ($padding > 0) {
            $paddingWidth = $padding * 2 - 1;
            $row .= str_pad("", $paddingWidth, " ");
            $row .= "|";
        }

        return $row . "\n";
    }

    /**
     * Render a documentation row for a group of consecutive bit fields and advance the offset
     *
     * @param non-empty-list<array{name: string, bits: int, offset: int}> $group
     */
    private static function documentBitFieldGroup(array $group, int &$offset): string
    {
        $totalBits = max(array_map(static fn (array $bf): int => $bf['offset'] + $bf['bits'], $group));
        $bytes = (int) ceil($totalBits / 8);
        $names = array_map(static fn (array $bf): string => sprintf('%s[%dbits]', $bf['name'], $bf['bits']), $group);

        $row = sprintf("| %d | %d | %s | BitField | - |  |\n", $offset, $bytes, implode(', ', $names));
        $offset += $bytes;

        return $row;
    }

    /**
     * Single-line representation of a condition value for documentation
     */
    private static function describeValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : (string) json_encode($value);
    }
}
