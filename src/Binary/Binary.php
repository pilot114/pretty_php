<?php

namespace PrettyPhp\Binary;

use Attribute;
use ReflectionClass;
use Exception;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Binary
{
    public function __construct(
        public string $format
    ) {
    }

    /**
     * Convert bit-based format specification to PHP pack format
     * Examples: "8" -> "C", "16" -> "n", "32" -> "N"
     */
    private static function convertBitFormatToPackFormat(string $format): string
    {
        if (is_numeric($format)) {
            $bits = (int) $format;
            return match ($bits) {
                8 => 'C',    // unsigned char (1 byte)
                16 => 'n',   // unsigned short (2 bytes, big endian)
                32 => 'N',   // unsigned long (4 bytes, big endian)
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

            $originalFormat = $attributes[0]->newInstance()->format;

            /** @var object $value */
            $value = $property->getValue($object);

            if (self::isNestedStructure($originalFormat)) {
                /** @var class-string $originalFormat */
                $totalSize += self::calculateObjectSize($value, $originalFormat);
            } else {
                $totalSize += self::getFormatSize($originalFormat, is_string($value) ? $value : null);
            }
        }

        return $totalSize;
    }

    public static function pack(object $object): string
    {
        $binaryData = '';
        foreach (new ReflectionClass($object)->getProperties() as $property) {
            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
            }

            $originalFormat = $attributes[0]->newInstance()->format;
            $value = $property->getValue($object);

            if (self::isNestedStructure($originalFormat)) {
                // Handle nested structure
                assert(is_object($value));
                $binaryData .= self::pack($value);
            } else {
                // Handle regular format
                $format = self::convertBitFormatToPackFormat($originalFormat);
                $binaryData .= pack($format, $value);
            }
        }

        return $binaryData;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public static function unpack(string $binaryData, string $className): object
    {
        $reflectionClass = new ReflectionClass($className);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $offset = 0;

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
            }

            $originalFormat = $attributes[0]->newInstance()->format;

            if (self::isNestedStructure($originalFormat)) {
                // Handle nested structure
                /** @var class-string $originalFormat */
                $nestedObject = self::unpack(substr($binaryData, $offset), $originalFormat);
                $nestedSize = self::calculateObjectSize($nestedObject, $originalFormat);
                $offset += $nestedSize;
                $property->setValue($object, $nestedObject);
            } else {
                // Handle regular format
                $format = self::convertBitFormatToPackFormat($originalFormat);

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
                $size = self::getFormatSize($originalFormat, is_string($value) ? $value : null);
                $offset += $size;
                $property->setValue($object, $value);
            }
        }

        return $object;
    }

    private static function getFormatSize(string $format, ?string $value = null): int
    {
        // Convert bit format to standard format first
        $packFormat = self::convertBitFormatToPackFormat($format);

        // Check for fixed-length string format (e.g., 'A6' for 6 bytes)
        if (preg_match('/^A(\d+)$/', $packFormat, $matches) === 1) {
            return (int) $matches[1];
        }

        return match ($packFormat) {
            'C' => 1,
            'n' => 2,
            'N' => 4,
            'A*' => $value !== null ? strlen($value) : 0,
            default => throw new Exception(sprintf("Unknown format size for '%s'.", $packFormat)),
        };
    }
}
