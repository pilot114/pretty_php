<?php

declare(strict_types=1);

use PrettyPhp\Binary\CapturedPacket;
use PrettyPhp\Binary\Socket;
use Tests\Support\TestPacketCapture;

function sendDatagrams(int $port, string ...$messages): void
{
    $client = Socket::udp();
    foreach ($messages as $message) {
        $client->sendTo($message, '127.0.0.1', $port);
    }

    $client->close();
}

describe('PacketCapture with local socket', function (): void {
    it('creates captures for specific protocols', function (string $factory, int $protocol): void {
        $capture = TestPacketCapture::$factory();
        expect($capture)->toBeInstanceOf(TestPacketCapture::class);
        $capture->start();
        expect($capture->requestedProtocol)->toBe($protocol);
        $capture->stop();
    })->with([
        ['all', 0x0003],
        ['icmp', 1],
        ['tcp', 6],
        ['udp', 17],
    ]);

    it('manages capture lifecycle', function (): void {
        $capture = new TestPacketCapture('lo');
        expect($capture->stop()->isCapturing())->toBeFalse();

        $capture->start();
        expect($capture->isCapturing())->toBeTrue();
        expect(fn (): TestPacketCapture => $capture->start())
            ->toThrow(\RuntimeException::class, 'Packet capture is already running');

        $capture->stop();
        expect($capture->isCapturing())->toBeFalse();
        expect(fn (): int => $capture->captureStream(fn (): bool => true))
            ->toThrow(\RuntimeException::class, 'Packet capture is not started');
    });

    it('captures packets with filters and handlers', function (): void {
        $capture = new TestPacketCapture();
        $capture->start();

        $handled = [];
        $capture->onPacket(function (CapturedPacket $packet) use (&$handled): void {
            $handled[] = $packet->data;
        });
        $capture->addFilter('keep');

        sendDatagrams($capture->port(), 'drop me', 'keep me');
        $packets = $capture->capture(1, 2.0);

        expect($packets)->toHaveCount(1);
        expect($packets[0]->data)->toBe('keep me');
        expect($handled)->toBe(['keep me']);
        expect($capture->getStats())->toBe(['captured' => 1, 'dropped' => 1]);

        $capture->clearFilters();
        expect($capture->capture(0, 0.05))->toBe([]);

        sendDatagrams($capture->port(), 'anything');
        expect($capture->capture(1, 2.0)[0]->data)->toBe('anything');
    });

    it('streams packets until callback stops', function (): void {
        $capture = new TestPacketCapture();
        $capture->start()->addFilter('keep');

        sendDatagrams($capture->port(), 'drop', 'keep 1', 'keep 2');
        $seen = [];
        $count = $capture->captureStream(function (CapturedPacket $packet) use (&$seen): bool {
            $seen[] = $packet->data;
            return count($seen) < 2;
        }, 2.0);

        expect($count)->toBe(2);
        expect($seen)->toBe(['keep 1', 'keep 2']);
        expect($capture->getStats()['dropped'])->toBe(1);

        expect($capture->captureStream(fn (): bool => true, 0.05))->toBe(0);
    });
});
