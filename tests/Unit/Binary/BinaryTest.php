<?php

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\TCPPacket;
use PrettyPhp\Binary\UDPPacket;
use PrettyPhp\Binary\ARPPacket;
use PrettyPhp\Binary\DNSPacket;
use PrettyPhp\Binary\HTTPPacket;
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

describe('TCPPacket', function (): void {
    it('can create with default checksum', function (): void {
        $packet = new TCPPacket(
            sourcePort: 80,
            destinationPort: 443,
            sequenceNumber: 1000
        );

        expect($packet->sourcePort)->toBe(80);
        expect($packet->destinationPort)->toBe(443);
        expect($packet->sequenceNumber)->toBe(1000);
        expect($packet->checksum)->toBeGreaterThan(0);
    });

    it('can pack and unpack TCPPacket', function (): void {
        $packet = new TCPPacket(
            sourcePort: 8080,
            destinationPort: 443,
            sequenceNumber: 12345,
            acknowledgmentNumber: 67890,
            dataOffsetAndFlags: 0x5002, // SYN flag
            windowSize: 65535
        );

        $packed = Binary::pack($packet);
        $tcpPacket = Binary::unpack($packed, TCPPacket::class);

        expect($tcpPacket->sourcePort)->toBe(8080);
        expect($tcpPacket->destinationPort)->toBe(443);
        expect($tcpPacket->sequenceNumber)->toBe(12345);
        expect($tcpPacket->acknowledgmentNumber)->toBe(67890);
        expect($tcpPacket->windowSize)->toBe(65535);
    });

    it('can set TCP flags', function (): void {
        $packet = new TCPPacket(
            sourcePort: 80,
            destinationPort: 443,
            sequenceNumber: 1000
        );

        $packet->setFlags(syn: true, ack: true);
        expect($packet->dataOffsetAndFlags & 0x12)->toBe(0x12); // SYN + ACK flags
    });
});

describe('UDPPacket', function (): void {
    it('can create with auto-calculated length', function (): void {
        $packet = new UDPPacket(
            sourcePort: 53,
            destinationPort: 53,
            data: 'test'
        );

        expect($packet->sourcePort)->toBe(53);
        expect($packet->destinationPort)->toBe(53);
        expect($packet->length)->toBe(12); // 8 bytes header + 4 bytes data
        expect($packet->checksum)->toBeGreaterThan(0);
    });

    it('can pack and unpack UDPPacket', function (): void {
        $packet = new UDPPacket(
            sourcePort: 12345,
            destinationPort: 53,
            data: 'dns query'
        );

        $packed = Binary::pack($packet);
        $udpPacket = Binary::unpack($packed, UDPPacket::class);

        expect($udpPacket->sourcePort)->toBe(12345);
        expect($udpPacket->destinationPort)->toBe(53);
        expect($udpPacket->data)->toBe('dns query');
    });

    it('can create with explicit length', function (): void {
        $packet = new UDPPacket(
            sourcePort: 80,
            destinationPort: 8080,
            length: 100,
            data: 'payload'
        );

        expect($packet->length)->toBe(100);
    });
});

describe('ARPPacket', function (): void {
    it('can create ARP request', function (): void {
        $packet = new ARPPacket(
            operation: ARPPacket::OPERATION_REQUEST,
            senderHardwareAddress: ARPPacket::macToBinary('00:11:22:33:44:55'),
            senderProtocolAddress: 0x0A000001,
            targetProtocolAddress: 0x0A000002
        );

        expect($packet->operation)->toBe(ARPPacket::OPERATION_REQUEST);
        expect($packet->hardwareType)->toBe(ARPPacket::HARDWARE_TYPE_ETHERNET);
        expect($packet->protocolType)->toBe(ARPPacket::PROTOCOL_TYPE_IPV4);
    });

    it('can pack and unpack ARPPacket', function (): void {
        $packet = new ARPPacket(
            operation: ARPPacket::OPERATION_REPLY,
            senderHardwareAddress: ARPPacket::macToBinary('AA:BB:CC:DD:EE:FF'),
            senderProtocolAddress: 0xC0A80001,
            targetHardwareAddress: ARPPacket::macToBinary('11:22:33:44:55:66'),
            targetProtocolAddress: 0xC0A80002
        );

        $packed = Binary::pack($packet);
        $arpPacket = Binary::unpack($packed, ARPPacket::class);

        expect($arpPacket->operation)->toBe(ARPPacket::OPERATION_REPLY);
        expect($arpPacket->senderProtocolAddress)->toBe(0xC0A80001);
        expect($arpPacket->targetProtocolAddress)->toBe(0xC0A80002);
    });

    it('can convert MAC address to binary and back', function (): void {
        $mac = '00:11:22:33:44:55';
        $binary = ARPPacket::macToBinary($mac);
        $converted = ARPPacket::binaryToMac($binary);

        expect($converted)->toBe($mac);
    });

    it('can handle MAC address with dashes', function (): void {
        $mac = '00-11-22-33-44-55';
        $binary = ARPPacket::macToBinary($mac);
        $converted = ARPPacket::binaryToMac($binary);

        expect($converted)->toBe('00:11:22:33:44:55');
    });
});

describe('DNSPacket', function (): void {
    it('can create DNS query', function (): void {
        $packet = new DNSPacket(
            transactionId: 12345,
            questionCount: 1
        );

        expect($packet->transactionId)->toBe(12345);
        expect($packet->questionCount)->toBe(1);
        expect($packet->flags)->toBe(0x0100); // Standard query
    });

    it('can pack and unpack DNSPacket', function (): void {
        $packet = new DNSPacket(
            transactionId: 1234,
            questionCount: 1,
            answerCount: 2,
            authorityCount: 0,
            additionalCount: 1,
            data: 'dns data'
        );

        $packed = Binary::pack($packet);
        $dnsPacket = Binary::unpack($packed, DNSPacket::class);

        expect($dnsPacket->transactionId)->toBe(1234);
        expect($dnsPacket->questionCount)->toBe(1);
        expect($dnsPacket->answerCount)->toBe(2);
        expect($dnsPacket->additionalCount)->toBe(1);
    });

    it('can set DNS flags', function (): void {
        $packet = new DNSPacket(transactionId: 1);
        $packet->setFlags(
            qr: DNSPacket::QR_RESPONSE,
            opcode: DNSPacket::OPCODE_QUERY,
            aa: true,
            rd: true,
            ra: true,
            rcode: DNSPacket::RCODE_NO_ERROR
        );

        expect($packet->getQR())->toBe(DNSPacket::QR_RESPONSE);
        expect($packet->isRecursionDesired())->toBeTrue();
        expect($packet->isRecursionAvailable())->toBeTrue();
        expect($packet->getRCode())->toBe(DNSPacket::RCODE_NO_ERROR);
    });

    it('can get individual flag values', function (): void {
        $packet = new DNSPacket(
            transactionId: 1,
            flags: 0x8180 // Standard response, recursion desired and available
        );

        expect($packet->getQR())->toBe(DNSPacket::QR_RESPONSE);
        expect($packet->isRecursionDesired())->toBeTrue();
        expect($packet->isRecursionAvailable())->toBeTrue();
    });
});

describe('HTTPPacket', function (): void {
    it('can create HTTP request', function (): void {
        $packet = HTTPPacket::createRequest(
            method: HTTPPacket::METHOD_GET,
            uri: '/api/test',
            headers: ['Host' => 'example.com']
        );

        expect($packet->isRequest)->toBeTrue();
        expect($packet->method)->toBe(HTTPPacket::METHOD_GET);
        expect($packet->uri)->toBe('/api/test');
        expect($packet->headers)->toBe(['Host' => 'example.com']);
    });

    it('can create HTTP response', function (): void {
        $packet = HTTPPacket::createResponse(
            statusCode: 200,
            headers: ['Content-Type' => 'application/json'],
            body: '{"status":"ok"}'
        );

        expect($packet->isRequest)->toBeFalse();
        expect($packet->statusCode)->toBe(200);
        expect($packet->reasonPhrase)->toBe('OK');
        expect($packet->body)->toBe('{"status":"ok"}');
    });

    it('can convert HTTP request to raw format', function (): void {
        $packet = HTTPPacket::createRequest(
            method: HTTPPacket::METHOD_POST,
            uri: '/api/data',
            headers: ['Content-Type' => 'application/json'],
            body: '{"key":"value"}'
        );

        $raw = $packet->toRaw();
        expect($raw)->toContain('POST /api/data HTTP/1.1');
        expect($raw)->toContain('Content-Type: application/json');
        expect($raw)->toContain('{"key":"value"}');
    });

    it('can convert HTTP response to raw format', function (): void {
        $packet = HTTPPacket::createResponse(
            statusCode: 404,
            body: 'Not Found'
        );

        $raw = $packet->toRaw();
        expect($raw)->toContain('HTTP/1.1 404 Not Found');
        expect($raw)->toContain('Not Found');
    });

    it('can parse raw HTTP request', function (): void {
        $raw = "GET /test HTTP/1.1\r\nHost: example.com\r\nUser-Agent: Test\r\n\r\n";
        $packet = HTTPPacket::fromRaw($raw);

        expect($packet->isRequest)->toBeTrue();
        expect($packet->method)->toBe('GET');
        expect($packet->uri)->toBe('/test');
        expect($packet->version)->toBe('HTTP/1.1');
        expect($packet->headers['Host'])->toBe('example.com');
        expect($packet->headers['User-Agent'])->toBe('Test');
    });

    it('can parse raw HTTP response', function (): void {
        $raw = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<html></html>";
        $packet = HTTPPacket::fromRaw($raw);

        expect($packet->isRequest)->toBeFalse();
        expect($packet->statusCode)->toBe(200);
        expect($packet->reasonPhrase)->toBe('OK');
        expect($packet->headers['Content-Type'])->toBe('text/html');
        expect($packet->body)->toBe('<html></html>');
    });

    it('can round-trip HTTP request through raw format', function (): void {
        $original = HTTPPacket::createRequest(
            method: 'PUT',
            uri: '/api/update',
            headers: ['Authorization' => 'Bearer token123'],
            body: 'data'
        );

        $raw = $original->toRaw();
        $parsed = HTTPPacket::fromRaw($raw);

        expect($parsed->method)->toBe('PUT');
        expect($parsed->uri)->toBe('/api/update');
        expect($parsed->headers['Authorization'])->toBe('Bearer token123');
        expect($parsed->body)->toBe('data');
    });
});
