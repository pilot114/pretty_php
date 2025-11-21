<?php

namespace PrettyPhp\Binary;

use Attribute;

/**
 * Attribute for defining bit fields within binary structures.
 *
 * Allows packing multiple values into individual bits rather than full bytes.
 *
 * Example:
 *   #[BitField(bits: 4, offset: 0)]  // First 4 bits
 *   public int $version;
 *
 *   #[BitField(bits: 4, offset: 4)]  // Next 4 bits
 *   public int $headerLength;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BitField
{
    public function __construct(
        public int $bits,      // Number of bits for this field
        public int $offset = 0 // Bit offset within the byte/group
    ) {
    }
}
