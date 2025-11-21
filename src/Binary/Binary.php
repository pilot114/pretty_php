<?php

namespace PrettyPhp\Binary;

use Attribute;
use ReflectionClass;
use Exception;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\SecurityConfig;
use PrettyPhp\Binary\Security\SecurityException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Binary
{
    public const ENDIAN_BIG = 'big';

    public const ENDIAN_LITTLE = 'little';

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

    /**
     * Calculate the total size of an object in bytes
     *
     * @param class-string $className
     * @phpstan-param class-string $className
     */
    private static function calculateObjectSize(object $object, string $className): int
    {
        $totalSize = 0;
        $reflectionClass = new ReflectionClass($className);

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                continue;
            }

            $binaryAttr = $attributes[0]->newInstance();
            $originalFormat = $binaryAttr->format;

            /** @var object $value */
            $value = $property->getValue($object);

            if (self::isNestedStructure($originalFormat)) {
                /** @var class-string $originalFormat */
                $totalSize += self::calculateObjectSize($value, $originalFormat);
            } else {
                $stringValue = is_string($value) ? $value : null;
                $totalSize += self::getFormatSize($originalFormat, $binaryAttr->endian, $stringValue);
            }
        }

        return $totalSize;
    }

    public static function pack(object $object): string
    {
        $binaryData = '';
        $bitFieldBuffer = 0;
        $bitFieldSize = 0;

        foreach (new ReflectionClass($object)->getProperties() as $property) {
            // Check for conditional packing
            $conditionalAttrs = $property->getAttributes(Conditional::class);
            if ($conditionalAttrs !== []) {
                $conditional = $conditionalAttrs[0]->newInstance();
                if (!$conditional->evaluate($object)) {
                    continue; // Skip this field
                }
            }

            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                // Check if it's a bit field
                $bitFieldAttrs = $property->getAttributes(BitField::class);
                if ($bitFieldAttrs === []) {
                    throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
                }

                // Handle bit field
                $bitField = $bitFieldAttrs[0]->newInstance();
                $value = $property->getValue($object);
                assert(is_int($value));

                // Create bit mask and add to buffer
                $mask = (1 << $bitField->bits) - 1;
                $bitFieldBuffer |= ($value & $mask) << $bitField->offset;
                $bitFieldSize = max($bitFieldSize, $bitField->offset + $bitField->bits);

                continue;
            }

            // Flush bit field buffer if we have one and moving to regular field
            if ($bitFieldSize > 0) {
                $bytes = (int) ceil($bitFieldSize / 8);
                for ($i = 0; $i < $bytes; $i++) {
                    $binaryData .= pack('C', ($bitFieldBuffer >> ($i * 8)) & 0xFF);
                }

                $bitFieldBuffer = 0;
                $bitFieldSize = 0;
            }

            $binaryAttr = $attributes[0]->newInstance();
            $originalFormat = $binaryAttr->format;
            $value = $property->getValue($object);

            if (self::isNestedStructure($originalFormat)) {
                // Handle nested structure
                assert(is_object($value));
                $binaryData .= self::pack($value);
            } else {
                // Handle regular format
                $format = self::convertBitFormatToPackFormat($originalFormat, $binaryAttr->endian);
                $binaryData .= pack($format, $value);
            }
        }

        // Flush any remaining bit field buffer
        if ($bitFieldSize > 0) {
            $bytes = (int) ceil($bitFieldSize / 8);
            for ($i = 0; $i < $bytes; $i++) {
                $binaryData .= pack('C', ($bitFieldBuffer >> ($i * 8)) & 0xFF);
            }
        }

        return $binaryData;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public static function unpack(string $binaryData, string $className, int $nestingDepth = 0): object
    {
        // Security: Check buffer size to prevent buffer overflow attacks
        $dataLength = strlen($binaryData);
        $maxBufferSize = SecurityConfig::getMaxBufferSize();
        if ($dataLength > $maxBufferSize) {
            throw new BufferOverflowException($dataLength, $maxBufferSize);
        }

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

        $reflectionClass = new ReflectionClass($className);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $offset = 0;
        $bitFieldBuffer = 0;
        $bitFieldBytesRead = 0;

        foreach ($reflectionClass->getProperties() as $property) {
            // Check for conditional unpacking
            $conditionalAttrs = $property->getAttributes(Conditional::class);
            if ($conditionalAttrs !== []) {
                $conditional = $conditionalAttrs[0]->newInstance();
                if (!$conditional->evaluate($object)) {
                    continue; // Skip this field
                }
            }

            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                // Check if it's a bit field
                $bitFieldAttrs = $property->getAttributes(BitField::class);
                if ($bitFieldAttrs === []) {
                    throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
                }

                // Handle bit field
                $bitField = $bitFieldAttrs[0]->newInstance();

                // Read bytes into buffer if needed
                $neededBytes = (int) ceil(($bitField->offset + $bitField->bits) / 8);
                while ($bitFieldBytesRead < $neededBytes) {
                    $byte = unpack('C', substr($binaryData, $offset, 1));
                    if ($byte === false || !isset($byte[1]) || !is_int($byte[1])) {
                        throw new Exception(sprintf("Failed to read byte for bit field '%s'.", $property->getName()));
                    }

                    $byteValue = $byte[1];
                    $bitFieldBuffer |= ($byteValue << ($bitFieldBytesRead * 8));
                    $bitFieldBytesRead++;
                    $offset++;
                }

                // Extract value from buffer
                $mask = (1 << $bitField->bits) - 1;
                $value = ($bitFieldBuffer >> $bitField->offset) & $mask;
                $property->setValue($object, $value);

                continue;
            }

            // Reset bit field buffer when moving to regular field
            $bitFieldBuffer = 0;
            $bitFieldBytesRead = 0;

            $binaryAttr = $attributes[0]->newInstance();
            $originalFormat = $binaryAttr->format;

            if (self::isNestedStructure($originalFormat)) {
                // Handle nested structure
                /** @var class-string $originalFormat */
                // Security: Pass incremented nesting depth to prevent infinite recursion
                $nestedObject = self::unpack(substr($binaryData, $offset), $originalFormat, $nestingDepth + 1);
                $nestedSize = self::calculateObjectSize($nestedObject, $originalFormat);
                $offset += $nestedSize;
                $property->setValue($object, $nestedObject);
            } else {
                // Handle regular format
                $format = self::convertBitFormatToPackFormat($originalFormat, $binaryAttr->endian);

                // Security: Validate we have enough data before unpacking
                $expectedSize = self::getFormatSize($originalFormat, $binaryAttr->endian);
                if ($offset + $expectedSize > $dataLength) {
                    throw new BufferOverflowException(
                        $offset + $expectedSize,
                        $dataLength
                    );
                }

                $unpacked = unpack($format, substr($binaryData, $offset));
                if ($unpacked === false) {
                    $message = sprintf("Failed to unpack binary data for property '%s'.", $property->getName());
                    throw new Exception($message);
                }

                $values = array_values($unpacked);
                if ($values === []) {
                    throw new Exception(sprintf("No values found when unpacking property '%s'.", $property->getName()));
                }

                $value = $values[0];
                $size = self::getFormatSize($originalFormat, $binaryAttr->endian, is_string($value) ? $value : null);
                $offset += $size;
                $property->setValue($object, $value);

                // Run validation if present
                $validateAttrs = $property->getAttributes(Validate::class);
                foreach ($validateAttrs as $validateAttr) {
                    $validator = $validateAttr->newInstance();
                    $validator->validate($property->getName(), $value);
                }
            }
        }

        return $object;
    }

    private static function getFormatSize(string $format, string $endian = self::ENDIAN_BIG, ?string $value = null): int
    {
        // Convert bit format to standard format first
        $packFormat = self::convertBitFormatToPackFormat($format, $endian);

        // Check for fixed-length string format (e.g., 'A6' for 6 bytes)
        if (preg_match('/^A(\d+)$/', $packFormat, $matches) === 1) {
            return (int) $matches[1];
        }

        return match ($packFormat) {
            'C' => 1,
            'n', 'v' => 2,  // 16-bit (big/little endian)
            'N', 'V' => 4,  // 32-bit (big/little endian)
            'J', 'P' => 8,  // 64-bit (big/little endian)
            'A*' => $value !== null ? strlen($value) : 0,
            default => throw new Exception(sprintf("Unknown format size for '%s'.", $packFormat)),
        };
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
                $valueStr = is_scalar($cond->value) ? (string) $cond->value : var_export($cond->value, true);
                $conditional = sprintf(' (if %s %s %s)', $cond->field, $cond->operator, $valueStr);
            }

            // Check for validation
            $validation = '';
            $validateAttrs = $property->getAttributes(Validate::class);
            foreach ($validateAttrs as $validateAttr) {
                $val = $validateAttr->newInstance();
                $constraints = [];
                if ($val->min !== null) {
                    $constraints[] = 'min=' . $val->min;
                }

                if ($val->max !== null) {
                    $constraints[] = 'max=' . $val->max;
                }

                if ($val->in !== null) {
                    $constraints[] = "in=[" . implode(',', $val->in) . "]";
                }

                if ($validation === '' && $constraints !== []) {
                    $validation = implode(', ', $constraints);
                }
            }

            // Check for bit field
            $bitFieldAttrs = $property->getAttributes(BitField::class);
            if ($bitFieldAttrs !== []) {
                $bitField = $bitFieldAttrs[0]->newInstance();
                $bitFieldGroup[] = [
                    'name' => $propertyName,
                    'bits' => $bitField->bits,
                    'offset' => $bitField->offset,
                    'conditional' => $conditional,
                ];
                continue;
            }

            // Flush bit field group if any
            if ($bitFieldGroup !== []) {
                $totalBits = 0;
                foreach ($bitFieldGroup as $bf) {
                    $totalBits = max($totalBits, $bf['offset'] + $bf['bits']);
                }

                $bytes = (int) ceil($totalBits / 8);

                $bitFieldNames = array_map(
                    fn(array $bf): string => sprintf('%s[%sbits]', $bf['name'], $bf['bits']),
                    $bitFieldGroup
                );
                $doc .= sprintf(
                    "| %d | %d | %s | BitField | - | %s |\n",
                    $offset,
                    $bytes,
                    implode(', ', $bitFieldNames),
                    ''
                );
                $offset += $bytes;
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
            $totalBits = 0;
            foreach ($bitFieldGroup as $bf) {
                $totalBits = max($totalBits, $bf['offset'] + $bf['bits']);
            }

            $bytes = (int) ceil($totalBits / 8);

            $bitFieldNames = array_map(
                fn(array $bf): string => sprintf('%s[%sbits]', $bf['name'], $bf['bits']),
                $bitFieldGroup
            );
            $doc .= sprintf(
                "| %d | %d | %s | BitField | - | %s |\n",
                $offset,
                $bytes,
                implode(', ', $bitFieldNames),
                ''
            );
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
        $bitFieldGroup = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

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
                $totalBits = 0;
                foreach ($bitFieldGroup as $bf) {
                    $totalBits = max($totalBits, $bf['offset'] + $bf['bits']);
                }

                // Create virtual field for bit field group
                foreach ($bitFieldGroup as $bf) {
                    $fields[] = [
                        'name' => $bf['name'],
                        'bits' => $bf['bits'],
                        'type' => 'bitfield',
                    ];
                }
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

        // Flush any remaining bit field group
        if ($bitFieldGroup !== []) {
            foreach ($bitFieldGroup as $bf) {
                $fields[] = [
                    'name' => $bf['name'],
                    'bits' => $bf['bits'],
                    'type' => 'bitfield',
                ];
            }
        }

        // Generate diagram
        $currentRow = [];
        $currentBits = 0;
        $firstRow = true;

        foreach ($fields as $field) {
            if ($field['type'] === 'nested') {
                // Flush current row
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits, $firstRow);
                    $firstRow = false;
                    $currentRow = [];
                    $currentBits = 0;
                }

                $doc .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
                $doc .= "|                    " . str_pad($field['name'] . " (nested structure)", 42, " ", STR_PAD_BOTH) . "|\n";
                continue;
            }

            if ($field['type'] === 'variable') {
                // Flush current row
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits, $firstRow);
                    $firstRow = false;
                    $currentRow = [];
                    $currentBits = 0;
                }

                $doc .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
                $doc .= "|                    " . str_pad($field['name'] . " (variable length)", 42, " ", STR_PAD_BOTH) . "|\n";
                continue;
            }

            $fieldBits = (int) $field['bits'];

            // Check if field fits in current row
            if ($currentBits + $fieldBits > 32) {
                // Render current row and start new one
                if ($currentRow !== []) {
                    $doc .= self::renderAsciiRow($currentRow, $currentBits, $firstRow);
                    $firstRow = false;
                }
                $currentRow = [$field];
                $currentBits = $fieldBits;
            } else {
                $currentRow[] = $field;
                $currentBits += $fieldBits;
            }

            // If current row is exactly 32 bits, render it
            if ($currentBits === 32) {
                $doc .= self::renderAsciiRow($currentRow, $currentBits, $firstRow);
                $firstRow = false;
                $currentRow = [];
                $currentBits = 0;
            }
        }

        // Render any remaining fields
        if ($currentRow !== []) {
            $doc .= self::renderAsciiRow($currentRow, $currentBits, $firstRow);
        }

        $doc .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";

        return $doc;
    }

    /**
     * Render a single row of ASCII diagram
     *
     * @param array<array{name: string, bits: int, type: string}> $fields
     */
    private static function renderAsciiRow(array $fields, int $totalBits, bool $firstRow): string
    {
        $row = '';

        if ($firstRow) {
            $row .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
        } else {
            $row .= "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
        }

        // Calculate padding
        $padding = 32 - $totalBits;

        $row .= "|";
        foreach ($fields as $field) {
            $fieldBits = (int) $field['bits'];
            $width = (int) ($fieldBits * 2); // Each bit takes 2 characters (+-) in the row

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
            $paddingWidth = (int) ($padding * 2);
            $row .= str_pad("", $paddingWidth, " ");
            $row .= "|";
        }

        $row .= "\n";

        return $row;
    }
}
