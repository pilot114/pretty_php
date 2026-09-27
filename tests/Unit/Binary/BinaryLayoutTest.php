<?php

declare(strict_types=1);

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\BitField;
use PrettyPhp\Binary\Conditional;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\SecurityConfig;
use PrettyPhp\Binary\Security\SecurityException;
use Tests\Support\TestDeepPacket;
use Tests\Support\TestInnerPacket;
use Tests\Support\TestNestedPacket;

mutates(Binary::class, \PrettyPhp\Binary\BinaryField::class);

describe('Binary byte layout', function (): void {
    it('packs and unpacks every fixed-size format with exact bytes', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public int $c = 0xAB;

            #[Binary('v')]
            public int $v = 0x1234;

            #[Binary('n')]
            public int $n = 0x1234;

            #[Binary('V')]
            public int $vv = 0x01020304;

            #[Binary('N')]
            public int $nn = 0x01020304;

            #[Binary('P')]
            public int $p = 0x0102030405060708;

            #[Binary('J')]
            public int $j = 0x0102030405060708;

            #[Binary('A3')]
            public string $a = 'xyz';

            #[Binary('16', Binary::ENDIAN_LITTLE)]
            public int $little16 = 0xBEEF;

            #[Binary('32', Binary::ENDIAN_LITTLE)]
            public int $little32 = 0xCAFEBABE;

            #[Binary('64', Binary::ENDIAN_LITTLE)]
            public int $little64 = 0x0A0B0C0D0E0F1011;

            #[Binary('8')]
            public int $tail = 0x7F;
        };

        $packed = Binary::pack($packet);
        expect(bin2hex($packed))->toBe(
            'ab' . '3412' . '1234' . '04030201' . '01020304' . '0807060504030201' . '0102030405060708'
            . bin2hex('xyz') . 'efbe' . 'bebafeca' . '11100f0e0d0c0b0a' . '7f'
        );

        $unpacked = Binary::unpack($packed, $packet::class);
        foreach (get_object_vars($packet) as $name => $value) {
            expect($unpacked->{$name})->toBe($value);
        }
    });

    it('masks bit field values and spreads groups over several bytes', function (): void {
        $packet = new class () {
            #[BitField(bits: 4)]
            public int $low = 0x1F;

            #[BitField(bits: 12, offset: 4)]
            public int $high = 0xABC;

            #[Binary('8')]
            public int $tail = 0x01;
        };

        $packed = Binary::pack($packet);
        expect(bin2hex($packed))->toBe('cfab01');

        $unpacked = Binary::unpack($packed, $packet::class);
        expect($unpacked->low)->toBe(0x0F);
        expect($unpacked->high)->toBe(0xABC);
        expect($unpacked->tail)->toBe(0x01);
    });

    it('rounds partial trailing bit field groups up to a whole byte', function (): void {
        $packet = new class () {
            #[Binary('8')]
            public int $head = 0x10;

            #[BitField(bits: 3)]
            public int $flags = 0b101;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('1005');
        expect(Binary::unpack("\x10\x05", $packet::class)->flags)->toBe(0b101);
    });

    it('packs nested structures between regular fields', function (): void {
        $packet = new class () {
            #[Binary('16')]
            public int $head = 0x0102;

            #[BitField(bits: 4)]
            public int $low = 0x3;

            #[BitField(bits: 4, offset: 4)]
            public int $high = 0x4;

            #[Binary(TestInnerPacket::class)]
            public TestInnerPacket $inner;

            #[Binary('8')]
            public int $tail = 0x07;

            public function __construct()
            {
                $this->inner = new TestInnerPacket(0x66);
            }
        };

        $expected = "\x01\x02\x43\x66\x07";
        expect(bin2hex(Binary::pack($packet)))->toBe(bin2hex($expected));

        $unpacked = Binary::unpack($expected, $packet::class);
        expect($unpacked->head)->toBe(0x0102);
        expect($unpacked->low)->toBe(0x3);
        expect($unpacked->high)->toBe(0x4);
        expect($unpacked->inner->innerValue)->toBe(0x66);
        expect($unpacked->tail)->toBe(0x07);
    });

    it('continues with following fields after a skipped conditional field', function (): void {
        $packet = new class () {
            #[Binary('8')]
            public int $type = 2;

            #[Conditional(field: 'type', operator: '==', value: 1)]
            #[Binary('16')]
            public int $optional = 0;

            #[Binary('8')]
            public int $tail = 0x33;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('0233');
        // tail differs from the property default, so a stopped loop would be detected
        $unpacked = Binary::unpack("\x02\x44", $packet::class);
        expect($unpacked->tail)->toBe(0x44);
    });
});

describe('Binary bit field groups', function (): void {
    it('flushes a 9-bit group into two bytes before the next field', function (): void {
        $packet = new class () {
            #[BitField(bits: 9)]
            public int $flags = 0x1FF;

            #[Binary('8')]
            public int $tail = 0x02;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('ff0102');
        $unpacked = Binary::unpack("\xff\x01\x02", $packet::class);
        expect($unpacked->flags)->toBe(0x1FF);
        expect($unpacked->tail)->toBe(0x02);
    });

    it('flushes a single-bit group', function (): void {
        $packet = new class () {
            #[BitField(bits: 1)]
            public int $flag = 1;

            #[Binary('8')]
            public int $tail = 0x02;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('0102');
    });

    it('keeps separate groups independent', function (): void {
        $packet = new class () {
            #[BitField(bits: 4)]
            public int $first = 0xA;

            #[Binary('8')]
            public int $middle = 0x11;

            #[BitField(bits: 4)]
            public int $second = 0x5;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('0a1105');
        $unpacked = Binary::unpack("\x0a\x11\x05", $packet::class);
        expect($unpacked->first)->toBe(0xA);
        expect($unpacked->second)->toBe(0x5);
    });

    it('writes trailing multi-byte groups in little-endian byte order', function (): void {
        $packet = new class () {
            #[Binary('8')]
            public int $head = 0x01;

            #[BitField(bits: 12)]
            public int $value = 0xABC;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('01bc0a');
        expect(Binary::unpack("\x01\xbc\x0a", $packet::class)->value)->toBe(0xABC);
    });

    it('packs nested structures separated by a regular field', function (): void {
        $packet = new class () {
            #[Binary(TestInnerPacket::class)]
            public TestInnerPacket $first;

            #[Binary('8')]
            public int $middle = 0x06;

            #[Binary(TestInnerPacket::class)]
            public TestInnerPacket $second;

            public function __construct()
            {
                $this->first = new TestInnerPacket(0x05);
                $this->second = new TestInnerPacket(0x07);
            }
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('050607');
    });
});

describe('Binary security limits', function (): void {
    afterEach(function (): void {
        SecurityConfig::reset();
    });

    it('accepts data exactly at the buffer size limit', function (): void {
        SecurityConfig::setMaxBufferSize(1);
        expect(Binary::unpack("\x05", TestInnerPacket::class)->innerValue)->toBe(5);
        expect(fn (): object => Binary::unpack("\x05\x06", TestInnerPacket::class))
            ->toThrow(BufferOverflowException::class, 'Requested size (2 bytes) exceeds maximum allowed size (1 bytes)');
    });

    it('accepts structures exactly at the nesting depth limit', function (): void {
        SecurityConfig::setMaxNestingDepth(1);
        expect(Binary::unpack("\x05\x06", TestNestedPacket::class)->outerValue)->toBe(6);
        expect(fn (): object => Binary::unpack("\x05\x06\x07", TestDeepPacket::class))
            ->toThrow(SecurityException::class, 'Maximum nesting depth exceeded: 2 > 1');
    });

    it('reports the exact number of missing bytes', function (): void {
        expect(fn (): object => Binary::unpack("\x01", TestNestedPacket::class))
            ->toThrow(BufferOverflowException::class, 'Requested size (2 bytes) exceeds maximum allowed size (1 bytes)');
    });
});

describe('Binary generated documentation', function (): void {
    it('documents structures exactly', function (string $class): void {
        expect(Binary::generateDocumentation($class))->toMatchSnapshot();
        expect(Binary::generateAsciiDiagram($class))->toMatchSnapshot();
    })->with([
        'annotated' => [\Tests\Support\TestAnnotatedPacket::class],
        'documented' => [\Tests\Support\TestDocumentedPacket::class],
        'tcp' => [\PrettyPhp\Binary\TCPPacket::class],
        'deep' => [TestDeepPacket::class],
    ]);
});
