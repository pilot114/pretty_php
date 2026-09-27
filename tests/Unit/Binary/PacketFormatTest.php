<?php

declare(strict_types=1);

use PrettyPhp\Binary\ARPPacket;
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\HTTPPacket;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\TCPPacket;
use PrettyPhp\Binary\Validate;

mutates(TCPPacket::class, HTTPPacket::class, ARPPacket::class, Validate::class, ICMPPacket::class, IPPacket::class);

describe('Internet checksum', function (): void {
    it('matches the RFC 1071 IPv4 header example', function (): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 0x73,
            identification: 0,
            flagsAndFragmentOffset: 0x4000,
            ttl: 0x40,
            protocol: 0x11,
            sourceIp: 0xC0A80001,
            destinationIp: 0xC0A800C7,
        );
        expect($packet->checksum)->toBe(0xB861);
        expect(IPPacket::calculate(clone $packet))->toBe(0xB861);
    });

    it('pads odd-length data with a zero byte', function (): void {
        $packet = new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1, data: 'abc');
        expect($packet->checksum)->toBe(0x339B);
    });
});

describe('TCPPacket layout', function (): void {
    it('uses a 20-byte header without flags by default', function (): void {
        $packet = new TCPPacket(sourcePort: 1, destinationPort: 2, sequenceNumber: 3);
        $packed = Binary::pack($packet);
        expect(bin2hex(substr($packed, 0, 16)))->toBe('00010002000000030000000050' . '00ffff');
        expect(bin2hex(substr($packed, 18)))->toBe('0000');
        expect(strlen($packed))->toBe(20);
    });

    it('sets each flag bit', function (string $flag, int $bit): void {
        $packet = new TCPPacket(sourcePort: 1, destinationPort: 2, sequenceNumber: 3);
        $packet->setFlags();

        expect($packet->dataOffsetAndFlags)->toBe(0x5000);
        $packet->setFlags(...[$flag => true]);
        expect($packet->dataOffsetAndFlags)->toBe(0x5000 | $bit);
        $packet->setFlags(...[$flag => true, 'dataOffset' => 6]);
        expect($packet->dataOffsetAndFlags)->toBe(0x6000 | $bit);
    })->with([
        ['fin', 0x001], ['syn', 0x002], ['rst', 0x004], ['psh', 0x008], ['ack', 0x010],
        ['urg', 0x020], ['ece', 0x040], ['cwr', 0x080], ['ns', 0x100],
    ]);
});

describe('HTTPPacket parsing', function (): void {
    it('uses request defaults', function (): void {
        $packet = new HTTPPacket();
        expect([$packet->method, $packet->uri, $packet->version, $packet->statusCode, $packet->reasonPhrase])
            ->toBe(['GET', '/', 'HTTP/1.1', 200, 'OK']);
        expect($packet->toRaw())->toBe("GET / HTTP/1.1\r\n\r\n");
    });

    it('parses partial request and response lines', function (): void {
        $request = HTTPPacket::fromRaw("DELETE /items\r\nHost: example.com:8080\r\n X-Spaced :  value \r\n\r\n");
        expect($request->isRequest)->toBeTrue();
        expect([$request->method, $request->uri, $request->version])->toBe(['DELETE', '/items', 'HTTP/1.1']);
        expect($request->headers)->toBe(['Host' => 'example.com:8080', 'X-Spaced' => 'value']);
        expect($request->body)->toBe('');

        $response = HTTPPacket::fromRaw("HTTP/1.0 404 Not Found Here\r\n\r\nbody");
        expect([$response->version, $response->statusCode, $response->reasonPhrase, $response->body])
            ->toBe(['HTTP/1.0', 404, 'Not Found Here', 'body']);

        $bare = HTTPPacket::fromRaw("HTTP/1.1\r\n\r\n");
        expect([$bare->statusCode, $bare->reasonPhrase])->toBe([200, 'OK']);
    });

    it('serializes bodies and custom reasons', function (): void {
        expect(HTTPPacket::createResponse(201, 'Made', ['X' => '1'], 'ok')->toRaw())
            ->toBe("HTTP/1.1 201 Made\r\nX: 1\r\n\r\nok");
        expect(HTTPPacket::createRequest('POST', '/p', [], 'data')->toRaw())
            ->toBe("POST /p HTTP/1.1\r\n\r\ndata");
    });
});

describe('ARPPacket layout', function (): void {
    it('uses RFC 826 values for Ethernet/IPv4 requests', function (): void {
        expect([ARPPacket::HARDWARE_TYPE_ETHERNET, ARPPacket::PROTOCOL_TYPE_IPV4, ARPPacket::OPERATION_REQUEST, ARPPacket::OPERATION_REPLY])
            ->toBe([1, 0x0800, 1, 2]);
        expect(bin2hex(Binary::pack(new ARPPacket())))
            ->toBe('0001' . '0800' . '06' . '04' . '0001' . '000000000000' . '00000000' . '000000000000' . '00000000');
    });
});

describe('Validate messages', function (): void {
    it('describes every violation', function (Validate $validator, mixed $value, string $message): void {
        expect(fn () => $validator->validate('field', $value))->toThrow(\Exception::class, $message);
    })->with([
        [new Validate(min: 10), 5, 'Value 5 is less than minimum 10'],
        [new Validate(max: 10), 11, 'Value 11 is greater than maximum 10'],
        [new Validate(in: [1, 2]), 3, 'Value 3 is not in allowed set'],
        [new Validate(in: [1, 2]), [1], "Value array (\n  0 => 1,\n) is not in allowed set"],
        [new Validate(notIn: [3]), 3, 'Value 3 is in disallowed set'],
        [new Validate(regex: '/^a/'), 'b', "Value 'b' does not match pattern '/^a/'"],
    ]);

    it('ignores constraints that do not apply to the value type', function (): void {
        new Validate(min: 10, max: 1, regex: '/^a/')->validate('field', 'abc');
        new Validate(regex: '/^a/')->validate('field', 5);
        expect(true)->toBeTrue();
    });
});
