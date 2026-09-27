<?php

declare(strict_types=1);

namespace PrettyPhp\Binary;

trait Checksum
{
    public static function calculate(object $object): int
    {
        if (property_exists($object, 'checksum')) {
            $object->checksum = 0;
        }

        // RFC 1071: one's complement sum of 16-bit words, odd data padded with a zero byte
        $data = Binary::pack($object);
        if (strlen($data) % 2 === 1) {
            $data .= "\0";
        }

        $checksum = array_sum((array) unpack('n*', $data));
        while ($checksum > 0xFFFF) {
            $checksum = ($checksum & 0xFFFF) + ($checksum >> 16);
        }

        return ~$checksum & 0xFFFF;
    }
}
