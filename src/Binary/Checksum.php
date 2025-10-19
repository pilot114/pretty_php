<?php

namespace PrettyPhp\Binary;

trait Checksum
{
    public static function calculate(object $object): int
    {
        if (property_exists($object, 'checksum')) {
            $object->checksum = 0;
        }

        $headerWithoutChecksum = Binary::pack($object);

        $checksum = 0;
        $bitLength = strlen($headerWithoutChecksum);

        for ($i = 0; $i < $bitLength; $i += 2) {
            $byte1 = ord($headerWithoutChecksum[$i]);
            $byte2 = isset($headerWithoutChecksum[$i + 1]) ? ord($headerWithoutChecksum[$i + 1]) : 0;
            $word = $byte1 << 8 | $byte2;
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}
