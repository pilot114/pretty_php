<?php

declare(strict_types=1);

use PrettyPhp\Binary\ARPPacket;
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\CapturedPacket;
use PrettyPhp\Binary\Conditional;
use PrettyPhp\Binary\DNSPacket;
use PrettyPhp\Binary\HTTPPacket;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\PacketResponse;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\TCPPacket;
use PrettyPhp\Binary\UDPPacket;
use PrettyPhp\Binary\Validate;

mutates(\PrettyPhp\Binary\ARPPacket::class, \PrettyPhp\Binary\CapturedPacket::class, \PrettyPhp\Binary\Conditional::class, \PrettyPhp\Binary\DNSPacket::class, \PrettyPhp\Binary\HTTPPacket::class, \PrettyPhp\Binary\PacketResponse::class, \PrettyPhp\Binary\Security\RateLimiter::class, \PrettyPhp\Binary\TCPPacket::class, \PrettyPhp\Binary\Validate::class);

function ipHeader(): string
{
    return Binary::pack(new IPPacket(
        versionAndHeaderLength: 0x45,
        typeOfService: 0,
        totalLength: 20,
        identification: 1,
        flagsAndFragmentOffset: 0,
        ttl: 64,
        protocol: 6,
        sourceIp: 0x7F000001,
        destinationIp: 0x7F000001,
    ));
}

describe('ARPPacket MAC helpers', function (): void {
    it('rejects malformed MAC addresses', function (): void {
        expect(fn (): string => ARPPacket::macToBinary('00:11:22'))->toThrow(\Exception::class, 'Invalid MAC address format');
        expect(fn (): string => ARPPacket::binaryToMac('abc'))->toThrow(\Exception::class, 'Invalid binary MAC address length');
    });
});

describe('HTTPPacket reason phrases', function (): void {
    it('maps known status codes', function (int $code, string $phrase): void {
        expect(HTTPPacket::createResponse($code)->reasonPhrase)->toBe($phrase);
    })->with([
        [201, 'Created'],
        [204, 'No Content'],
        [301, 'Moved Permanently'],
        [302, 'Found'],
        [304, 'Not Modified'],
        [400, 'Bad Request'],
        [401, 'Unauthorized'],
        [403, 'Forbidden'],
        [405, 'Method Not Allowed'],
        [500, 'Internal Server Error'],
        [502, 'Bad Gateway'],
        [503, 'Service Unavailable'],
    ]);
});

describe('TCPPacket flags', function (): void {
    it('sets every flag bit', function (): void {
        $packet = new TCPPacket(sourcePort: 1, destinationPort: 2, sequenceNumber: 0);
        $packet->setFlags(fin: true, syn: true, rst: true, psh: true, ack: true, urg: true, ece: true, cwr: true, ns: true);

        expect($packet->dataOffsetAndFlags)->toBe((5 << 12) | 0x1FF);
    });
});

describe('DNSPacket opcode', function (): void {
    it('extracts opcode from flags', function (): void {
        $packet = new DNSPacket(transactionId: 1, flags: 2 << 11);
        expect($packet->getOpcode())->toBe(2);
    });
});

describe('Conditional operators', function (): void {
    it('evaluates all operators', function (string $operator, int $value, bool $expected): void {
        $object = new class () {
            public int $field = 5;
        };
        expect(new Conditional('field', $operator, $value)->evaluate($object))->toBe($expected);
    })->with([
        ['!=', 4, true],
        ['<', 6, true],
        ['<=', 5, true],
        ['>=', 6, false],
        ['??', 5, false],
    ]);

    it('returns false for missing field', function (): void {
        expect(new Conditional('missing')->evaluate(new \stdClass()))->toBeFalse();
    });
});

describe('Validate regex', function (): void {
    it('reports regex mismatch', function (): void {
        expect(fn () => new Validate(regex: '/^\d+$/')->validate('field', 'abc'))
            ->toThrow(\Exception::class, "does not match pattern");
    });
});

describe('RateLimiter state', function (): void {
    it('skips checks when disabled and re-enables', function (): void {
        $limiter = new RateLimiter(1, 60);
        $limiter->disable();
        $limiter->checkLimit();
        $limiter->checkLimit();
        $limiter->enable();
        $limiter->checkLimit();

        expect($limiter->tryOperation())->toBeFalse();
    });

    it('returns zero wait time when tokens are available', function (): void {
        expect(new RateLimiter(5, 60)->getTimeUntilNextToken())->toBe(0.0);
    });
});

describe('CapturedPacket parsing', function (): void {
    it('parses packet layers', function (): void {
        $icmp = Binary::pack(new ICMPPacket(type: 8, code: 0, identifier: 1, sequenceNumber: 1));
        $tcp = Binary::pack(new TCPPacket(sourcePort: 80, destinationPort: 8080, sequenceNumber: 1));
        $udp = Binary::pack(new UDPPacket(sourcePort: 53, destinationPort: 5353, length: 8));

        $make = fn (string $payload): CapturedPacket => new CapturedPacket(
            data: ipHeader() . $payload,
            timestamp: 0.0,
            sourceAddress: '127.0.0.1',
            sourcePort: 0,
            length: 20 + strlen($payload),
            interface: 'lo',
        );

        expect($make('')->parseIP())->toBeInstanceOf(IPPacket::class);
        expect($make('')->parse(IPPacket::class)->ttl)->toBe(64);
        expect($make($icmp)->parseICMP()->type)->toBe(8);
        expect($make($tcp)->parseTCP()->destinationPort)->toBe(8080);
        expect($make($udp)->parseUDP()->destinationPort)->toBe(5353);
    });
});

describe('PacketResponse', function (): void {
    it('reports response presence and parses IP header', function (): void {
        $empty = new PacketResponse('req', null, null, 0, 3, 0);
        expect($empty->hasResponse())->toBeFalse();
        expect($empty->getResponseIPPacket())->toBeNull();

        $response = new PacketResponse('req', ipHeader(), '127.0.0.1', 0, 3, 20, 1.5);
        expect($response->hasResponse())->toBeTrue();
        expect($response->getResponseIPPacket()?->ttl)->toBe(64);
    });
});
