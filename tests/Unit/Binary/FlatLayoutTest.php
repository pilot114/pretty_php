<?php

declare(strict_types=1);

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\FlatLayout;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Validate;
use Tests\Support\TestFlatBasePacket;
use Tests\Support\TestFlatFixedOuterPacket;
use Tests\Support\TestFlatTailOuterPacket;
use Tests\Support\TestFlatTailPacket;
use Tests\Support\TestInnerPacket;

mutates(FlatLayout::class, Binary::class);

describe('Flat binary structures', function (): void {
    it('packs and unpacks private, readonly, untyped, float and mixed properties', function (): void {
        $packet = new class () {
            #[Binary('C')]
            private int $hidden = 0x0A;

            #[Binary('n')]
            public readonly int $fixed;

            /** @var int */
            #[Binary('N')]
            public $untyped = 0x01020304;

            #[Binary('n')]
            public float $real = 0x0506;

            #[Binary('A3')]
            public mixed $code = 'abc';

            #[Binary('A*')]
            public string $tail = 'xyz';

            public function __construct()
            {
                $this->fixed = 0x0B0C;
            }

            public function hidden(): int
            {
                return $this->hidden;
            }
        };

        $packed = Binary::pack($packet);
        expect(bin2hex($packed))->toBe('0a0b0c01020304050661626378797a');

        $unpacked = Binary::unpack($packed, $packet::class);
        expect($unpacked->hidden())->toBe(0x0A)
            ->and($unpacked->fixed)->toBe(0x0B0C)
            ->and($unpacked->untyped)->toBe(0x01020304)
            ->and($unpacked->real)->toBe((float) 0x0506)
            ->and($unpacked->code)->toBe('abc')
            ->and($unpacked->tail)->toBe('xyz');
    });

    it('packs and unpacks a structure without fields', function (): void {
        $packet = new class () {
        };

        expect(Binary::pack($packet))->toBe('')
            ->and(Binary::unpack('', $packet::class))->toBeInstanceOf($packet::class);
    });

    it('keeps schema order for inherited properties', function (): void {
        $packet = new class () extends TestFlatBasePacket {
            #[Binary('n')]
            public int $own = 0x2233;
        };

        $packed = Binary::pack($packet);
        expect(bin2hex($packed))->toBe('223311');

        $unpacked = Binary::unpack("\x44\x55\x66", $packet::class);
        expect($unpacked->own)->toBe(0x4455)
            ->and($unpacked->inherited)->toBe(0x66);
    });

    it('reports an uninitialized property when packing', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public int $first = 1;

            #[Binary('C')]
            public int $second;
        };

        // The engine cuts the anonymous class name at its NUL byte
        expect(fn (): string => Binary::pack($packet))->toThrow(
            Error::class,
            'Typed property class@anonymous::$second must not be accessed before initialization'
        );
    });

    it('ignores dynamic properties when packing', function (): void {
        $packet = new #[AllowDynamicProperties] class () {
            #[Binary('C')]
            public int $value = 7;
        };
        $packet->extra = 8;

        expect(bin2hex(Binary::pack($packet)))->toBe('07');
    });

    it('coerces unpacked values like ReflectionProperty::setValue()', function (): void {
        $packet = new class () {
            #[Binary('n')]
            public string $port = '';

            #[Binary('A4')]
            public int $code = 0;

            #[Binary('n')]
            public int|string $either = 0;
        };

        $unpacked = Binary::unpack("\x01\xBB" . '1234' . "\x00\x05", $packet::class);
        expect($unpacked->port)->toBe('443')
            ->and($unpacked->code)->toBe(1234)
            ->and($unpacked->either)->toBe(5);
    });

    it('reads and writes static properties', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public static int $shared = 3;

            #[Binary('C')]
            public int $value = 4;
        };

        expect(bin2hex(Binary::pack($packet)))->toBe('0304');

        $unpacked = Binary::unpack("\x05\x06", $packet::class);
        expect($packet::$shared)->toBe(5)
            ->and($unpacked->value)->toBe(6);
    });

    it('lets a variable-length field consume the rest even when fields follow it', function (): void {
        $packet = new class () {
            #[Binary('A*')]
            public string $text = '';

            #[Binary('C')]
            public int $after = 0;
        };

        expect(fn (): object => Binary::unpack('abc', $packet::class))->toThrow(
            BufferOverflowException::class,
            'Buffer overflow protection: Requested size (4 bytes) exceeds maximum allowed size (3 bytes)'
        );
    });

    it('rejects variable-length formats other than A*', function (): void {
        $packet = new class () {
            #[Binary('H*')]
            public string $hex = '';
        };

        expect(fn (): object => Binary::unpack('ab', $packet::class))
            ->toThrow(Exception::class, "Unknown format size for 'H*'.");
    });

    it('reports the first field that does not fit into short data', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public int $a = 0;

            #[Binary('n')]
            public int $b = 0;

            #[Binary('N')]
            public int $c = 0;
        };

        expect(fn (): object => Binary::unpack("\x01\x02\x03\x04", $packet::class))->toThrow(
            BufferOverflowException::class,
            'Buffer overflow protection: Requested size (7 bytes) exceeds maximum allowed size (4 bytes)'
        );
    });

    it('unpacks data of exactly the fixed size', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public int $a = 0;

            #[Binary('n')]
            public int $b = 0;
        };

        $unpacked = Binary::unpack("\x01\x02\x03", $packet::class);
        expect($unpacked->a)->toBe(1)
            ->and($unpacked->b)->toBe(0x0203);
    });

    it('runs validators in field order', function (): void {
        $packet = new class () {
            #[Binary('C')]
            public int $plain = 0;

            #[Validate(min: 10)]
            #[Binary('C')]
            public int $first = 0;

            #[Validate(max: 5)]
            #[Validate(in: [1, 2])]
            #[Binary('C')]
            public int $second = 0;
        };

        $valid = Binary::unpack("\x00\x0A\x02", $packet::class);
        expect($valid->first)->toBe(10)
            ->and($valid->second)->toBe(2);

        expect(fn (): object => Binary::unpack("\x00\x09\x09", $packet::class))
            ->toThrow(Exception::class, "Validation failed for property 'first': Value 9 is less than minimum 10");
        expect(fn (): object => Binary::unpack("\x00\x0A\x03", $packet::class))
            ->toThrow(Exception::class, "Validation failed for property 'second': Value 3 is not in allowed set");
    });

    it('advances past a nested fixed-size flat structure', function (): void {
        $outer = new TestFlatFixedOuterPacket(new TestInnerPacket(0x21), 0x4243);

        $packed = Binary::pack($outer);
        expect(bin2hex($packed))->toBe('214243');

        $unpacked = Binary::unpack($packed, TestFlatFixedOuterPacket::class);
        expect($unpacked->inner->innerValue)->toBe(0x21)
            ->and($unpacked->after)->toBe(0x4243);
    });

    it('advances past the variable-length tail of a nested flat structure', function (): void {
        $unpacked = Binary::unpack("\x07tail", TestFlatTailOuterPacket::class);

        expect($unpacked->inner)->toBeInstanceOf(TestFlatTailPacket::class)
            ->and($unpacked->inner->kind)->toBe(7)
            ->and($unpacked->inner->payload)->toBe('tail')
            ->and($unpacked->rest)->toBe('');
    });
});
