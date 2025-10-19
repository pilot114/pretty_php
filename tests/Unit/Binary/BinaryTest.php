<?php

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use Tests\Support\TestNestedPacket;
use Tests\Support\TestInnerPacket;

describe('Binary', function (): void {
    it('can pack simple 8-bit values', function (): void {
        $packet = new class {
            #[Binary('8')]
            public int $value = 42;
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('C', 42));
    });

    it('can pack 16-bit values', function (): void {
        $packet = new class {
            #[Binary('16')]
            public int $value = 1234;
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('n', 1234));
    });

    it('can pack 32-bit values', function (): void {
        $packet = new class {
            #[Binary('32')]
            public int $value = 123456;
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('N', 123456));
    });

    it('can pack multiple fields', function (): void {
        $packet = new class {
            #[Binary('8')]
            public int $type = 8;

            #[Binary('8')]
            public int $code = 0;

            #[Binary('16')]
            public int $checksum = 0;
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('CCn', 8, 0, 0));
    });

    it('can pack string with A* format', function (): void {
        $packet = new class {
            #[Binary('A*')]
            public string $data = 'hello';
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('A*', 'hello'));
    });

    it('can unpack simple 8-bit values', function (): void {
        $binaryData = pack('C', 42);

        $className = new class {
            #[Binary('8')]
            public int $value = 0;
        };

        $obj = Binary::unpack($binaryData, $className::class);
        expect($obj->value)->toBe(42);
    });

    it('can unpack 16-bit values', function (): void {
        $binaryData = pack('n', 1234);

        $className = new class {
            #[Binary('16')]
            public int $value = 0;
        };

        $obj = Binary::unpack($binaryData, $className::class);
        expect($obj->value)->toBe(1234);
    });

    it('can unpack 32-bit values', function (): void {
        $binaryData = pack('N', 123456);

        $className = new class {
            #[Binary('32')]
            public int $value = 0;
        };

        $obj = Binary::unpack($binaryData, $className::class);
        expect($obj->value)->toBe(123456);
    });

    it('can unpack multiple fields', function (): void {
        $binaryData = pack('CCn', 8, 0, 1234);

        $className = new class {
            #[Binary('8')]
            public int $type = 0;

            #[Binary('8')]
            public int $code = 0;

            #[Binary('16')]
            public int $checksum = 0;
        };

        $obj = Binary::unpack($binaryData, $className::class);
        expect($obj->type)->toBe(8);
        expect($obj->code)->toBe(0);
        expect($obj->checksum)->toBe(1234);
    });

    it('can pack and unpack nested structures', function (): void {
        $innerPacket = new TestInnerPacket(innerValue: 123);
        $nestedPacket = new TestNestedPacket(inner: $innerPacket, outerValue: 45);

        $packed = Binary::pack($nestedPacket);
        expect($packed)->toBe(pack('CC', 123, 45));

        $testNestedPacket = Binary::unpack($packed, TestNestedPacket::class);
        expect($testNestedPacket->inner->innerValue)->toBe(123);
        expect($testNestedPacket->outerValue)->toBe(45);
    });

    it('can use standard pack formats', function (): void {
        $packet = new class {
            #[Binary('C')]
            public int $value = 42;
        };

        $packed = Binary::pack($packet);
        expect($packed)->toBe(pack('C', 42));
    });

    it('throws exception for unsupported bit format', function (): void {
        $packet = new class {
            #[Binary('7')]
            public int $value = 42;
        };

        expect(fn(): string => Binary::pack($packet))
            ->toThrow(Exception::class, 'Unsupported bit format: 7 bits');
    });

    it('throws exception when format is not defined', function (): void {
        $packet = new class {
            public int $value = 42;
        };

        expect(fn(): string => Binary::pack($packet))
            ->toThrow(Exception::class, "Format for property 'value' is not defined");
    });

    it('throws exception when unpack fails due to insufficient data', function (): void {
        $binaryData = pack('C', 42);  // Only 1 byte

        $className = new class {
            #[Binary('8')]
            public int $field1 = 0;

            #[Binary('8')]
            public int $field2 = 0;  // This will fail - not enough data
        };

        expect(fn(): object => Binary::unpack($binaryData, $className::class))
            ->toThrow(Exception::class, "Failed to unpack binary data for property 'field2'");
    });

    it('can pack and unpack ICMPPacket', function (): void {
        $packet = new ICMPPacket(
            type: 8,
            code: 0,
            checksum: 0,
            identifier: 1,
            sequenceNumber: 1,
            data: 'test'
        );

        $packed = Binary::pack($packet);
        $icmpPacket = Binary::unpack($packed, ICMPPacket::class);

        expect($icmpPacket->type)->toBe(8);
        expect($icmpPacket->code)->toBe(0);
        expect($icmpPacket->identifier)->toBe(1);
        expect($icmpPacket->sequenceNumber)->toBe(1);
    });

    it('can pack and unpack IPPacket', function (): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 20,
            identification: 54321,
            flagsAndFragmentOffset: 0,
            ttl: 64,
            protocol: 1,
            checksum: 0,
            sourceIp: 0,
            destinationIp: 0,
            data: ''
        );

        $packed = Binary::pack($packet);
        $ipPacket = Binary::unpack($packed, IPPacket::class);

        expect($ipPacket->versionAndHeaderLength)->toBe(0x45);
        expect($ipPacket->typeOfService)->toBe(0);
        expect($ipPacket->totalLength)->toBe(20);
        expect($ipPacket->identification)->toBe(54321);
        expect($ipPacket->ttl)->toBe(64);
        expect($ipPacket->protocol)->toBe(1);
    });
});

describe('ICMPPacket', function (): void {
    it('can create with default checksum', function (): void {
        $packet = new ICMPPacket(
            type: 8,
            code: 0
        );

        expect($packet->type)->toBe(8);
        expect($packet->code)->toBe(0);
        expect($packet->checksum)->toBeGreaterThan(0);
    });

    it('can create with explicit checksum', function (): void {
        $packet = new ICMPPacket(
            type: 8,
            code: 0,
            checksum: 12345
        );

        expect($packet->checksum)->toBe(12345);
    });

    it('can create with all parameters', function (): void {
        $packet = new ICMPPacket(
            type: 8,
            code: 0,
            checksum: 0,
            identifier: 100,
            sequenceNumber: 200,
            data: 'test data'
        );

        expect($packet->identifier)->toBe(100);
        expect($packet->sequenceNumber)->toBe(200);
        expect($packet->data)->toBe('test data');
    });
});

describe('IPPacket', function (): void {
    it('can create with default checksum', function (): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 20,
            identification: 1,
            flagsAndFragmentOffset: 0,
            ttl: 64,
            protocol: 1
        );

        expect($packet->versionAndHeaderLength)->toBe(0x45);
        expect($packet->checksum)->toBeGreaterThan(0);
    });

    it('can create with explicit checksum', function (): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 20,
            identification: 1,
            flagsAndFragmentOffset: 0,
            ttl: 64,
            protocol: 1,
            checksum: 12345
        );

        expect($packet->checksum)->toBe(12345);
    });

    it('can create with all parameters', function (): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 20,
            identification: 1,
            flagsAndFragmentOffset: 0,
            ttl: 64,
            protocol: 1,
            checksum: 0,
            sourceIp: 0x7F000001,
            destinationIp: 0x08080808,
            data: 'payload'
        );

        expect($packet->sourceIp)->toBe(0x7F000001);
        expect($packet->destinationIp)->toBe(0x08080808);
        expect($packet->data)->toBe('payload');
    });
});
