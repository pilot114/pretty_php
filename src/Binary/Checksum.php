<?php

namespace PrettyPhp\Binary;

trait Checksum
{
    public static function calculate(object $object): int
    {
        $object->checksum = 0;
        $headerWithoutChecksum = Binary::pack($object);

        $checksum = 0;
        $bitLength = strlen($headerWithoutChecksum);

        for ($i = 0; $i < $bitLength; $i += 2) {
            $word = ord($headerWithoutChecksum[$i]) << 8 | ord($headerWithoutChecksum[$i + 1]);
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}
