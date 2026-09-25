<?php

declare(strict_types=1);

use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\IPPacket;
use PrettyPhp\Binary\PacketPrinter;
use PrettyPhp\Binary\PacketResponse;

function captureOutput(callable $callback): string
{
    ob_start();
    $callback();

    return (string) ob_get_clean();
}

describe('PacketPrinter', function (): void {
    it('prints transmissions with and without label', function (): void {
        expect(captureOutput(fn () => PacketPrinter::printTransmission('send', 'AB', 'Ping')))
            ->toContain('Ping >>> 2 bytes');
        expect(captureOutput(fn () => PacketPrinter::printTransmission('receive', 'AB')))
            ->toContain('<<< 2 bytes');
    });

    it('prints ICMP packet', function (): void {
        $output = captureOutput(fn () => PacketPrinter::printICMPPacket(
            new ICMPPacket(type: 8, code: 0, identifier: 7, sequenceNumber: 3, data: 'xyz')
        ));
        expect($output)->toContain('Type:       8')->toContain('Identifier: 7')->toContain('Data:       3 bytes');
    });

    it('prints IP packet with protocol names', function (int $protocol, string $name): void {
        $packet = new IPPacket(
            versionAndHeaderLength: 0x45,
            typeOfService: 0,
            totalLength: 20,
            identification: 1,
            flagsAndFragmentOffset: 0,
            ttl: 64,
            protocol: $protocol,
            sourceIp: 0,
            destinationIp: 0x7F000001,
        );

        $output = captureOutput(fn () => PacketPrinter::printIPPacket($packet));
        expect($output)
            ->toContain('IPv4')
            ->toContain("Protocol:       {$name} ({$protocol})")
            ->toContain('Source IP:      0.0.0.0')
            ->toContain('Destination IP: 127.0.0.1');
    })->with([
        [1, 'ICMP'], [6, 'TCP'], [17, 'UDP'], [41, 'IPv6'], [47, 'GRE'],
        [50, 'ESP'], [51, 'AH'], [58, 'ICMPv6'], [99, 'Unknown'],
    ]);

    it('prints response statistics', function (): void {
        $full = captureOutput(fn () => PacketPrinter::printResponseStats(
            new PacketResponse('req', 'resp', '10.0.0.1', 53, 3, 4, 1.234)
        ));
        expect($full)->toContain('Response Time: 1.23 ms')->toContain('10.0.0.1:53')->toContain('Bytes Received: 4');

        $minimal = captureOutput(fn () => PacketPrinter::printResponseStats(
            new PacketResponse('req', null, '10.0.0.1', 0, 3, 0)
        ));
        expect($minimal)->not->toContain('Response Time')->not->toContain('10.0.0.1:');

        $noSource = captureOutput(fn () => PacketPrinter::printResponseStats(
            new PacketResponse('req', null, null, 0, 3, 0)
        ));
        expect($noSource)->not->toContain('Source:');
    });

    it('prints sections and status messages', function (): void {
        expect(captureOutput(fn () => PacketPrinter::printSection('Title')))->toContain('  Title');
        expect(captureOutput(fn () => PacketPrinter::printError('bad')))->toContain('ERROR')->toContain('│ bad');
        expect(captureOutput(fn () => PacketPrinter::printSuccess('good')))->toContain('SUCCESS')->toContain('│ good');
    });
});
