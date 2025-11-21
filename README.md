# Pretty PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://www.php.net/)
[![Latest Version](https://img.shields.io/packagist/v/pilot114/pretty_php.svg)](https://packagist.org/packages/pilot114/pretty_php)
[![Total Downloads](https://img.shields.io/packagist/dt/pilot114/pretty_php.svg)](https://packagist.org/packages/pilot114/pretty_php)
[![CI Status](https://github.com/pilot114/pretty_php/actions/workflows/ci.yml/badge.svg)](https://github.com/pilot114/pretty_php/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/pilot114/pretty_php/branch/main/graph/badge.svg)](https://codecov.io/gh/pilot114/pretty_php)

A modern, object-oriented wrapper for PHP's standard library with consistent API design and immutable data structures.

## Features

- 🔗 **Chainable API** - Fluent interface for all operations
- 🛡️ **Type-safe** - Full PHP 8.4+ type declarations with PHPStan max level
- 🔒 **Immutable** - All operations return new instances
- 📦 **Zero dependencies** - Pure PHP implementation
- ⚡ **High performance** - Optimized for speed
- 🧪 **Well tested** - Comprehensive test coverage with 700+ tests
- 🌐 **Network protocols** - Built-in support for TCP, UDP, IP, ICMP, ARP, DNS, HTTP
- 🔐 **Security first** - Buffer overflow protection, rate limiting, security audits
- 🎯 **Functional types** - Result and Option types for safer error handling

## Installation

```bash
composer require prettyph/pretty-php
```

## Quick Start

```php
use function PrettyPhp\{str, arr, file, path, num, json, datetime};

// String manipulation
$result = str('  Hello World  ')
    ->trim()
    ->upper()
    ->replace('WORLD', 'PHP');
echo $result; // "HELLO PHP"

// Array operations
$numbers = arr([1, 2, 3, 4, 5])
    ->filter(fn($n) => $n % 2 === 0)
    ->map(fn($n) => $n * 2)
    ->sum();
echo $numbers; // 12

// Numeric operations
$price = num(1234.567)
    ->round(2)
    ->currency('$', 2);
echo $price; // "$1,234.57"

// JSON operations
$json = json(['name' => 'John', 'age' => 30])
    ->fromData()
    ->pretty()
    ->toStr();

// DateTime operations
$date = datetime('2024-01-15')
    ->addDays(7)
    ->format('Y-m-d'); // "2024-01-22"

echo datetime('2024-01-01')->ago(); // "10 months ago"

// File operations
file('/path/to/file.txt')
    ->write('Hello, Pretty PHP!')
    ->copy('/path/to/backup.txt');

// Path manipulation
$config = path('/home/user')
    ->join('projects', 'myapp', 'config.php');
```

## Core Components

### Base Wrappers
- **Str** - String manipulation with 30+ methods
- **Arr** - Array operations with functional programming support
- **File** - File I/O operations
- **Path** - Path manipulation utilities
- **Num** - Numeric operations with formatting, validation, and base conversion
- **Json** - JSON operations with path queries and safe error handling
- **DateTime** - Fluent date/time API with timezone support and relative time

### Binary System
Attribute-based binary data packing/unpacking for network protocols:

```php
use PrettyPhp\Binary\{Binary, IPPacket, ICMPPacket, TCPPacket, UDPPacket};

// Create and pack ICMP packet
$packet = new ICMPPacket(
    type: 8,
    code: 0,
    identifier: 12345,
    sequenceNumber: 1,
    data: str_repeat("\x00", 32)
);

$binary = Binary::pack($packet);
$parsed = Binary::unpack($binary, ICMPPacket::class);
```

#### Network Protocols
- **IPPacket** - IPv4 packets
- **ICMPPacket** - ICMP (ping) packets
- **TCPPacket** - TCP packets with checksum
- **UDPPacket** - UDP packets
- **ARPPacket** - ARP protocol
- **DNSPacket** - DNS queries/responses
- **HTTPPacket** - Basic HTTP packets

#### Network Utilities
- **Socket** - High-level socket operations
- **RawSocket** - Low-level raw socket handling
- **NetworkInterface** - Network interface information
- **PacketCapture** - Packet sniffing capabilities

#### Binary Features
- BitField support for bit-level operations
- Conditional packing for dynamic structures
- Built-in validation and security checks
- Buffer overflow protection
- Rate limiting for network operations

### Functional Programming
Railway-oriented programming with type-safe error handling:

```php
use PrettyPhp\Functional\{Result, Option};
use function PrettyPhp\{json};

// Result type for error handling
$result = json('{"name": "John"}')->fromString()->decode();
if ($result->isOk()) {
    $data = $result->unwrap();
}

// Option type for nullable values
$option = Option::fromNullable($value);
if ($option->isSome()) {
    echo $option->unwrap();
}
```

## Security

Pretty PHP includes built-in security features for safe network operations:

- **Buffer Overflow Protection** - Automatic size validation for binary operations
- **Rate Limiting** - Configurable rate limiting for network operations
- **Security Auditing** - Built-in security audit tools
- **Input Validation** - Comprehensive validation for all binary structures

For security concerns, please see [SECURITY.md](SECURITY.md).

## Documentation

- 📖 [Full Documentation](AGENTS.md)
- 🗺️ [Roadmap](ROADMAP.md)
- 🔐 [Security Policy](SECURITY.md)

## Requirements

- PHP 8.4+
- Extensions: `ext-ctype`, `ext-fileinfo`, `ext-sockets`, `ext-posix`

## Development

```bash
composer test      # Run tests
composer coverage  # Test coverage report
composer check     # Static analysis
composer fix       # Auto-fix code style
composer bench     # Performance benchmarks
```
