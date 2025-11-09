# Pretty PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://www.php.net/)

A modern, object-oriented wrapper for PHP's standard library with consistent API design and immutable data structures.

## Features

- 🔗 **Chainable API** - Fluent interface for all operations
- 🛡️ **Type-safe** - Full PHP 8.4+ type declarations
- 🔒 **Immutable** - All operations return new instances
- 📦 **Zero dependencies** - Pure PHP implementation
- ⚡ **High performance** - Optimized for speed
- 🧪 **Well tested** - 84%+ code coverage, extensive test suite

## Installation

```bash
composer require prettyph/pretty-php
```

## Quick Start

```php
use function PrettyPhp\{str, arr, file, path};

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

### Binary System
Attribute-based binary data packing/unpacking for network protocols:

```php
use PrettyPhp\Binary\{Binary, IPPacket, ICMPPacket};

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

## Documentation

- 📖 [Full Documentation](AGENTS.md)
- 🗺️ [Roadmap](ROADMAP.md)
- 📝 [Changelog](CHANGELOG.md)
- 🤝 [Contributing](CONTRIBUTING.md)

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
