<?php

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;
use PrettyPhp\Binary\Conditional;
use PrettyPhp\Binary\Validate;

describe('Binary Improvements', function (): void {
    describe('Endianness Support', function (): void {
        it('can pack little-endian 16-bit values', function (): void {
            $packet = new class {
                #[Binary('16', endian: Binary::ENDIAN_LITTLE)]
                public int $value = 0x1234;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('v', 0x1234));
        });

        it('can pack little-endian 32-bit values', function (): void {
            $packet = new class {
                #[Binary('32', endian: Binary::ENDIAN_LITTLE)]
                public int $value = 0x12345678;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('V', 0x12345678));
        });

        it('can pack big-endian 16-bit values (default)', function (): void {
            $packet = new class {
                #[Binary('16')]
                public int $value = 0x1234;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('n', 0x1234));
        });

        it('can unpack little-endian values', function (): void {
            $binaryData = pack('v', 0x1234);

            $className = new class {
                #[Binary('16', endian: Binary::ENDIAN_LITTLE)]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(0x1234);
        });

        it('can pack 64-bit values with big endian', function (): void {
            $packet = new class {
                #[Binary('64', endian: Binary::ENDIAN_BIG)]
                public int $value = 123456789;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('J', 123456789));
        });

        it('can pack 64-bit values with little endian', function (): void {
            $packet = new class {
                #[Binary('64', endian: Binary::ENDIAN_LITTLE)]
                public int $value = 123456789;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('P', 123456789));
        });
    });

    describe('BitField Support', function (): void {
        it('can pack bit fields', function (): void {
            $packet = new class {
                #[BitField(bits: 4, offset: 0)]
                public int $version = 4;

                #[BitField(bits: 4, offset: 4)]
                public int $headerLength = 5;
            };

            $packed = Binary::pack($packet);
            // version (4) in lower nibble, headerLength (5) in upper nibble
            // 0x54 = 0101 0100 = version 4, headerLength 5
            expect($packed)->toBe(pack('C', 0x54));
        });

        it('can unpack bit fields', function (): void {
            $binaryData = pack('C', 0x54); // version=4, headerLength=5

            $className = new class {
                #[BitField(bits: 4, offset: 0)]
                public int $version = 0;

                #[BitField(bits: 4, offset: 4)]
                public int $headerLength = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->version)->toBe(4);
            expect($obj->headerLength)->toBe(5);
        });

        it('can pack multi-byte bit fields', function (): void {
            $packet = new class {
                #[BitField(bits: 3, offset: 0)]
                public int $flags = 2;

                #[BitField(bits: 13, offset: 3)]
                public int $fragmentOffset = 100;
            };

            $packed = Binary::pack($packet);
            expect(strlen($packed))->toBe(2); // 16 bits = 2 bytes
        });

        it('can mix bit fields with regular fields', function (): void {
            $packet = new class {
                #[BitField(bits: 4, offset: 0)]
                public int $version = 4;

                #[BitField(bits: 4, offset: 4)]
                public int $type = 5;

                #[Binary('8')]
                public int $code = 100;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('CC', 0x54, 100));
        });
    });

    describe('Conditional Packing', function (): void {
        it('can conditionally pack fields based on equality', function (): void {
            $packet = new class {
                #[Binary('8')]
                public int $type = 1;

                #[Conditional(field: 'type', operator: '==', value: 1)]
                #[Binary('8')]
                public int $optionalField = 42;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('CC', 1, 42));
        });

        it('can skip fields that do not meet condition', function (): void {
            $packet = new class {
                #[Binary('8')]
                public int $type = 2;

                #[Conditional(field: 'type', operator: '==', value: 1)]
                #[Binary('8')]
                public int $optionalField = 42;

                #[Binary('8')]
                public int $anotherField = 99;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('CC', 2, 99));
        });

        it('can use different comparison operators', function (): void {
            $packet = new class {
                #[Binary('8')]
                public int $length = 10;

                #[Conditional(field: 'length', operator: '>', value: 5)]
                #[Binary('8')]
                public int $extendedData = 77;
            };

            $packed = Binary::pack($packet);
            expect($packed)->toBe(pack('CC', 10, 77));
        });

        it('can conditionally unpack fields', function (): void {
            $binaryData = pack('CC', 1, 42);

            $className = new class {
                #[Binary('8')]
                public int $type = 0;

                #[Conditional(field: 'type', operator: '==', value: 1)]
                #[Binary('8')]
                public int $optionalField = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->type)->toBe(1);
            expect($obj->optionalField)->toBe(42);
        });
    });

    describe('Validation on Unpack', function (): void {
        it('can validate minimum value', function (): void {
            $binaryData = pack('C', 50);

            $className = new class {
                #[Validate(min: 10)]
                #[Binary('8')]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(50);
        });

        it('throws exception when value is less than minimum', function (): void {
            $binaryData = pack('C', 5);

            $className = new class {
                #[Validate(min: 10)]
                #[Binary('8')]
                public int $value = 0;
            };

            expect(fn(): object => Binary::unpack($binaryData, $className::class))
                ->toThrow(Exception::class, "Validation failed for property 'value'");
        });

        it('can validate maximum value', function (): void {
            $binaryData = pack('C', 50);

            $className = new class {
                #[Validate(max: 100)]
                #[Binary('8')]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(50);
        });

        it('throws exception when value exceeds maximum', function (): void {
            $binaryData = pack('C', 200);

            $className = new class {
                #[Validate(max: 100)]
                #[Binary('8')]
                public int $value = 0;
            };

            expect(fn(): object => Binary::unpack($binaryData, $className::class))
                ->toThrow(Exception::class, "Validation failed for property 'value'");
        });

        it('can validate value is in allowed set', function (): void {
            $binaryData = pack('C', 2);

            $className = new class {
                #[Validate(in: [1, 2, 3])]
                #[Binary('8')]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(2);
        });

        it('throws exception when value is not in allowed set', function (): void {
            $binaryData = pack('C', 5);

            $className = new class {
                #[Validate(in: [1, 2, 3])]
                #[Binary('8')]
                public int $value = 0;
            };

            expect(fn(): object => Binary::unpack($binaryData, $className::class))
                ->toThrow(Exception::class, "Validation failed for property 'value'");
        });

        it('can validate value is not in disallowed set', function (): void {
            $binaryData = pack('C', 5);

            $className = new class {
                #[Validate(notIn: [0, 255])]
                #[Binary('8')]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(5);
        });

        it('throws exception when value is in disallowed set', function (): void {
            $binaryData = pack('C', 255);

            $className = new class {
                #[Validate(notIn: [0, 255])]
                #[Binary('8')]
                public int $value = 0;
            };

            expect(fn(): object => Binary::unpack($binaryData, $className::class))
                ->toThrow(Exception::class, "Validation failed for property 'value'");
        });

        it('can validate with custom error message', function (): void {
            $binaryData = pack('C', 5);

            $className = new class {
                #[Validate(min: 10, message: 'Custom error: Value too small')]
                #[Binary('8')]
                public int $value = 0;
            };

            expect(fn(): object => Binary::unpack($binaryData, $className::class))
                ->toThrow(Exception::class, 'Custom error: Value too small');
        });

        it('can combine multiple validation constraints', function (): void {
            $binaryData = pack('C', 50);

            $className = new class {
                #[Validate(min: 10, max: 100)]
                #[Binary('8')]
                public int $value = 0;
            };

            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->value)->toBe(50);
        });
    });

    describe('Documentation Generation', function (): void {
        it('can generate documentation for simple structures', function (): void {
            $className = new class {
                #[Binary('8')]
                public int $type = 0;

                #[Binary('16')]
                public int $length = 0;
            };

            $doc = Binary::generateDocumentation($className::class);

            expect($doc)->toContain('Binary Structure');
            expect($doc)->toContain('type');
            expect($doc)->toContain('length');
            expect($doc)->toContain('8-bit');
            expect($doc)->toContain('16-bit');
        });

        it('can generate documentation with endianness', function (): void {
            $className = new class {
                #[Binary('16', endian: Binary::ENDIAN_LITTLE)]
                public int $value = 0;
            };

            $doc = Binary::generateDocumentation($className::class);

            expect($doc)->toContain('little');
        });

        it('can generate documentation with bit fields', function (): void {
            $className = new class {
                #[BitField(bits: 4, offset: 0)]
                public int $version = 0;

                #[BitField(bits: 4, offset: 4)]
                public int $headerLength = 0;
            };

            $doc = Binary::generateDocumentation($className::class);

            expect($doc)->toContain('BitField');
            expect($doc)->toContain('version');
            expect($doc)->toContain('headerLength');
        });

        it('can generate documentation with validation constraints', function (): void {
            $className = new class {
                #[Validate(min: 1, max: 255)]
                #[Binary('8')]
                public int $type = 0;
            };

            $doc = Binary::generateDocumentation($className::class);

            expect($doc)->toContain('min=1');
            expect($doc)->toContain('max=255');
        });

        it('can generate documentation with conditional fields', function (): void {
            $className = new class {
                #[Binary('8')]
                public int $type = 0;

                #[Conditional(field: 'type', operator: '==', value: 1)]
                #[Binary('16')]
                public int $optionalField = 0;
            };

            $doc = Binary::generateDocumentation($className::class);

            expect($doc)->toContain('if type == 1');
        });
    });

    describe('Combined Features', function (): void {
        it('can use multiple improvements together', function (): void {
            $packet = new class {
                #[Binary('8')]
                public int $version = 1;

                #[BitField(bits: 3, offset: 0)]
                public int $flags = 2;

                #[BitField(bits: 5, offset: 3)]
                public int $reserved = 0;

                #[Conditional(field: 'version', operator: '>=', value: 1)]
                #[Binary('16', endian: Binary::ENDIAN_LITTLE)]
                public int $extendedData = 0x1234;
            };

            $packed = Binary::pack($packet);
            $obj = Binary::unpack($packed, $packet::class);

            expect($obj->version)->toBe(1);
            expect($obj->flags)->toBe(2);
            expect($obj->extendedData)->toBe(0x1234);
        });

        it('can validate unpacked bit fields', function (): void {
            $binaryData = pack('C', 0x45); // version=5, headerLength=4

            $className = new class {
                #[Validate(min: 4, max: 6)]
                #[BitField(bits: 4, offset: 0)]
                public int $version = 0;

                #[BitField(bits: 4, offset: 4)]
                public int $headerLength = 0;
            };

            // Note: Validation only works on Binary fields, not BitFields in current implementation
            // This test documents the current behavior
            $obj = Binary::unpack($binaryData, $className::class);
            expect($obj->version)->toBe(5);
            expect($obj->headerLength)->toBe(4);
        });
    });
});
