<?php

namespace PrettyPhp\Binary;

class HexPrint
{
    /**
     * Convert binary data to hexadecimal string representation
     */
    public static function toHex(string $data, string $separator = ' '): string
    {
        $hex = [];
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $hex[] = sprintf('%02x', ord($data[$i]));
        }

        return implode($separator, $hex);
    }

    /**
     * Parse hexadecimal string back to binary data
     */
    public static function fromHex(string $hex): string
    {
        // Remove common separators
        $hex = preg_replace('/[\s:.-]/', '', $hex);

        if ($hex === null || $hex === '') {
            return '';
        }

        // Validate hex string
        if (!ctype_xdigit($hex)) {
            throw new \InvalidArgumentException('Invalid hexadecimal string');
        }

        // Ensure even length
        if (strlen($hex) % 2 !== 0) {
            throw new \InvalidArgumentException('Hexadecimal string must have even length');
        }

        $binary = '';
        $length = strlen($hex);

        for ($i = 0; $i < $length; $i += 2) {
            $binary .= chr((int) hexdec(substr($hex, $i, 2)));
        }

        return $binary;
    }

    /**
     * Convert binary data to hexadecimal dump with ASCII representation
     */
    public static function dump(string $data, int $bytesPerLine = 16): string
    {
        $length = strlen($data);
        $output = [];

        for ($i = 0; $i < $length; $i += $bytesPerLine) {
            $chunk = substr($data, $i, $bytesPerLine);
            $chunkLength = strlen($chunk);

            // Offset
            $offset = sprintf('%08x', $i);

            // Hex representation
            $hex = [];
            for ($j = 0; $j < $chunkLength; $j++) {
                $hex[] = sprintf('%02x', ord($chunk[$j]));
            }

            // Pad hex if needed
            $hexString = implode(' ', $hex);
            $padding = ($bytesPerLine - $chunkLength) * 3;
            $hexString .= str_repeat(' ', $padding);

            // ASCII representation
            $ascii = '';
            for ($j = 0; $j < $chunkLength; $j++) {
                $byte = ord($chunk[$j]);
                $ascii .= ($byte >= 32 && $byte <= 126) ? $chunk[$j] : '.';
            }

            $output[] = sprintf('%s  %s  |%s|', $offset, $hexString, $ascii);
        }

        return implode("\n", $output);
    }

    /**
     * Convert binary data to formatted hex blocks (4 bytes per block)
     */
    public static function toBlocks(string $data, int $blocksPerLine = 4): string
    {
        $output = [];
        $currentLine = [];

        for ($i = 0; $i < strlen($data); $i += 4) {
            $chunk = substr($data, $i, 4);
            $hex = [];

            for ($j = 0; $j < strlen($chunk); $j++) {
                $hex[] = sprintf('%02x', ord($chunk[$j]));
            }

            $currentLine[] = implode('', $hex);

            if (count($currentLine) >= $blocksPerLine) {
                $output[] = implode(' ', $currentLine);
                $currentLine = [];
            }
        }

        // Add remaining blocks
        if ($currentLine !== []) {
            $output[] = implode(' ', $currentLine);
        }

        return implode("\n", $output);
    }

    /**
     * Format binary data as colored hex dump (ANSI colors)
     */
    public static function colorDump(string $data, int $bytesPerLine = 16): string
    {
        $length = strlen($data);
        $output = [];

        for ($i = 0; $i < $length; $i += $bytesPerLine) {
            $chunk = substr($data, $i, $bytesPerLine);
            $chunkLength = strlen($chunk);

            // Offset in cyan
            $offset = "\033[36m" . sprintf('%08x', $i) . "\033[0m";

            // Hex representation with colors
            $hex = [];
            for ($j = 0; $j < $chunkLength; $j++) {
                $byte = ord($chunk[$j]);
                $color = self::getByteColor($byte);
                $hex[] = $color . sprintf('%02x', $byte) . "\033[0m";
            }

            // Pad hex if needed
            $hexString = implode(' ', $hex);
            $padding = ($bytesPerLine - $chunkLength) * 3;
            $hexString .= str_repeat(' ', $padding);

            // ASCII representation
            $ascii = '';
            for ($j = 0; $j < $chunkLength; $j++) {
                $byte = ord($chunk[$j]);
                $char = ($byte >= 32 && $byte <= 126) ? $chunk[$j] : '.';
                $ascii .= $char;
            }

            $output[] = sprintf('%s  %s  |%s|', $offset, $hexString, $ascii);
        }

        return implode("\n", $output);
    }

    /**
     * Get ANSI color code for byte value
     */
    private static function getByteColor(int $byte): string
    {
        if ($byte === 0) {
            return "\033[90m"; // Dark gray for null bytes
        }

        if ($byte >= 32 && $byte <= 126) {
            return "\033[92m"; // Green for printable ASCII
        }

        if ($byte >= 128) {
            return "\033[93m"; // Yellow for high bytes
        }

        return "\033[91m"; // Red for control characters
    }
}
