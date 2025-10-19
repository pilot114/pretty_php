<?php

use PrettyPhp\Binary\HexPrint;

describe('HexPrint', function (): void {
    it('can convert binary data to hex string with default separator', function (): void {
        $data = "\x01\x02\x03\x0A\xFF";
        $result = HexPrint::toHex($data);
        expect($result)->toBe('01 02 03 0a ff');
    });

    it('can convert binary data to hex string with custom separator', function (): void {
        $data = "\x01\x02\x03";
        $result = HexPrint::toHex($data, ':');
        expect($result)->toBe('01:02:03');
    });

    it('can convert binary data to hex string with no separator', function (): void {
        $data = "\x01\x02\x03";
        $result = HexPrint::toHex($data, '');
        expect($result)->toBe('010203');
    });

    it('can convert empty data to hex', function (): void {
        $result = HexPrint::toHex('');
        expect($result)->toBe('');
    });

    it('can create hex dump with offset, hex, and ASCII', function (): void {
        $data = "Hello, World!";
        $result = HexPrint::dump($data);

        expect($result)->toContain('00000000');
        expect($result)->toContain('48 65 6c 6c 6f'); // "Hello"
        expect($result)->toContain('|Hello, World!|');
    });

    it('can create hex dump with custom bytes per line', function (): void {
        $data = "1234567890";
        $result = HexPrint::dump($data, 5);

        $lines = explode("\n", $result);
        expect($lines)->toHaveCount(2);
        expect($lines[0])->toContain('|12345|');
        expect($lines[1])->toContain('|67890|');
    });

    it('can handle non-printable characters in dump', function (): void {
        $data = "\x00\x01\x02\x03\x04";
        $result = HexPrint::dump($data);

        expect($result)->toContain('00 01 02 03 04');
        expect($result)->toContain('|.....|');
    });

    it('can create hex dump with mixed printable and non-printable', function (): void {
        $data = "A\x00B\x01C";
        $result = HexPrint::dump($data);

        expect($result)->toContain('|A.B.C|');
    });

    it('can format data as hex blocks', function (): void {
        $data = "\x01\x02\x03\x04\x05\x06\x07\x08";
        $result = HexPrint::toBlocks($data, 2);

        $lines = explode("\n", $result);
        expect($lines[0])->toBe('01020304 05060708');
    });

    it('can format data as hex blocks with single block per line', function (): void {
        $data = "\x01\x02\x03\x04\x05\x06\x07\x08";
        $result = HexPrint::toBlocks($data, 1);

        $lines = explode("\n", $result);
        expect($lines)->toHaveCount(2);
        expect($lines[0])->toBe('01020304');
        expect($lines[1])->toBe('05060708');
    });

    it('can handle partial blocks', function (): void {
        $data = "\x01\x02\x03";
        $result = HexPrint::toBlocks($data);

        expect($result)->toBe('010203');
    });

    it('can parse hex string back to binary', function (): void {
        $hex = '01 02 03 0a ff';
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe("\x01\x02\x03\x0A\xFF");
    });

    it('can parse hex string with colons', function (): void {
        $hex = '01:02:03';
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe("\x01\x02\x03");
    });

    it('can parse hex string with hyphens', function (): void {
        $hex = '01-02-03';
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe("\x01\x02\x03");
    });

    it('can parse hex string with dots', function (): void {
        $hex = '01.02.03';
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe("\x01\x02\x03");
    });

    it('can parse hex string without separators', function (): void {
        $hex = '010203';
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe("\x01\x02\x03");
    });

    it('can parse empty hex string', function (): void {
        $result = HexPrint::fromHex('');
        expect($result)->toBe('');
    });

    it('throws exception for invalid hex string', function (): void {
        expect(fn(): string => HexPrint::fromHex('0g'))
            ->toThrow(\InvalidArgumentException::class, 'Invalid hexadecimal string');
    });

    it('throws exception for odd length hex string', function (): void {
        expect(fn(): string => HexPrint::fromHex('123'))
            ->toThrow(\InvalidArgumentException::class, 'Hexadecimal string must have even length');
    });

    it('can round-trip binary data through hex', function (): void {
        $original = "Hello, World!\x00\xFF";
        $hex = HexPrint::toHex($original, '');
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe($original);
    });

    it('can create colored hex dump', function (): void {
        $data = "A\x00\xFF";
        $result = HexPrint::colorDump($data);

        // Should contain ANSI color codes
        expect($result)->toContain("\033[");
        expect($result)->toContain('00000000');
    });

    it('can create colored dump with custom bytes per line', function (): void {
        $data = "1234567890ABCDEF";
        $result = HexPrint::colorDump($data, 8);

        $lines = explode("\n", $result);
        expect($lines)->toHaveCount(2);
    });

    it('handles null bytes in colored dump', function (): void {
        $data = "\x00\x00\x00";
        $result = HexPrint::colorDump($data);

        // Dark gray for null bytes
        expect($result)->toContain("\033[90m");
    });

    it('handles printable ASCII in colored dump', function (): void {
        $data = "ABC";
        $result = HexPrint::colorDump($data);

        // Green for printable ASCII
        expect($result)->toContain("\033[92m");
    });

    it('handles high bytes in colored dump', function (): void {
        $data = "\xFF\xFE";
        $result = HexPrint::colorDump($data);

        // Yellow for high bytes
        expect($result)->toContain("\033[93m");
    });

    it('handles control characters in colored dump', function (): void {
        $data = "\x01\x02\x03";
        $result = HexPrint::colorDump($data);

        // Red for control characters
        expect($result)->toContain("\033[91m");
    });

    it('can dump large data', function (): void {
        $data = str_repeat("A", 256);
        $result = HexPrint::dump($data);

        $lines = explode("\n", $result);
        expect($lines)->toHaveCount(16); // 256 bytes / 16 bytes per line
    });

    it('preserves data integrity through hex conversion', function (): void {
        // Test all byte values
        $data = '';
        for ($i = 0; $i < 256; $i++) {
            $data .= chr($i);
        }

        $hex = HexPrint::toHex($data, '');
        $result = HexPrint::fromHex($hex);

        expect($result)->toBe($data);
    });
});
