<?php

namespace PrettyPhp\Binary;

use ReflectionClass;
use ReflectionProperty;
use Exception;

class BinarySerializer
{
    public static function pack(object $object): string
    {
        $binaryData = '';
        foreach (new ReflectionClass($object)->getProperties() as $property) {
            $format = self::getPropertyFormat($property);
            if ($format === null) {
                throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
            }

            $binaryData .= pack($format, $property->getValue($object));
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
            $format = self::getPropertyFormat($property);
            if ($format === null) {
                throw new Exception(sprintf("Format for property '%s' is not defined.", $property->getName()));
            }

            $unpacked = unpack($format, substr($binaryData, $offset));
            if ($unpacked === false) {
                throw new Exception(sprintf("Failed to unpack binary data for property '%s'.", $property->getName()));
            }

            $values = array_values($unpacked);
            if ($values === []) {
                throw new Exception(sprintf("No values found when unpacking property '%s'.", $property->getName()));
            }

            $value = $values[0];
            $size = self::getFormatSize($format, is_string($value) ? $value : null);
            $offset += $size;
            $property->setValue($object, $value);
        }

        return $object;
    }

    private static function getPropertyFormat(ReflectionProperty $reflectionProperty): ?string
    {
        $attributes = $reflectionProperty->getAttributes(Binary::class);
        if ($attributes !== []) {
            return $attributes[0]->newInstance()->format;
        }

        return null;
    }

    private static function getFormatSize(string $format, ?string $value = null): int
    {
        return match ($format) {
            'C' => 1,
            'n' => 2,
            'N' => 4,
            'A*' => $value !== null ? strlen($value) : 0,
            default => throw new Exception(sprintf("Unknown format size for '%s'.", $format)),
        };
    }
}
