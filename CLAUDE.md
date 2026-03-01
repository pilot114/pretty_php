# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Pretty PHP is a modern, object-oriented wrapper library for PHP's standard library (PHP 8.4+). It provides immutable data structures with fluent/chainable APIs and zero runtime dependencies. Package: `prettyph/pretty-php`.

## Commands

```bash
# Run tests (Pest 4.0)
composer test

# Run tests with coverage
composer coverage

# Run all quality checks (PHPStan + Rector dry-run + PHPCS)
composer check

# Auto-fix code style (Rector + PHPCBF)
composer fix

# Run benchmarks
composer bench

# Run a single test file
vendor/bin/pest tests/Unit/Base/StrTest.php

# Run a specific test by name
vendor/bin/pest --filter="test name"
```

## Architecture

### Module Structure

- **`src/Base/`** — Core type wrappers: `Str`, `Arr`, `File`, `Path`, `Num`, `Json`, `DateTime`, `Date`, `DateInterval`, `Timezone`, `Session`. All are `readonly` classes returning new instances on mutation (immutable pattern).
- **`src/Binary/`** — Attribute-based binary data packing/unpacking. Uses `#[Binary(...)]` PHP 8 attributes on class properties to declare binary structure, then reflection to serialize/deserialize. Includes network protocol implementations (ICMP, IP, TCP, UDP, ARP, DNS, HTTP) and security utilities.
- **`src/Functional/`** — Railway-oriented types: `Result<T,E>` (Ok/Err) and `Option<T>` (Some/None) with `map`, `flatMap`, `unwrap` chains.
- **`src/Curl/`** — OOP wrapper for PHP's curl extension: `Curl`, `CurlHandle`, `CurlMultiHandle`, `CurlShareHandle` with enum-based HTTP methods/versions.
- **`src/System/`** — POSIX function wrappers: `Posix` facade, `PosixUser`, `PosixProcess`, `PosixSystem`, `PosixFile`.
- **`src/functions.php`** — Global helper functions (`str()`, `arr()`, `file()`, `path()`, `num()`, `json()`, `datetime()`, `ok()`, `err()`, `some()`, `none()`) auto-loaded via composer.

### Key Patterns

- **Immutability**: All wrapper classes are `readonly`. Operations return new instances.
- **Fluent API**: Methods return `self`/`static` for chaining.
- **Generic types**: Full PHPDoc `@template` generics for PHPStan type safety.
- **Static factory methods**: `Result::ok()`, `Result::err()`, `Result::from()`, `Option::some()`, `Option::none()`, `Option::from()`.

## Quality Standards

- **PHPStan**: Level `max` with strict rules, checked exception tracking, and `checkImplicitMixed: true`
- **Code style**: PSR-12 via PHPCS (120 char warning, 150 char hard limit)
- **Rector**: Applied with PHP 8.4 level, strict booleans, dead code removal, type declarations, early return
- **Namespace**: `PrettyPhp\` for source, `PrettyPhp\Tests\` for tests

## Testing

Tests use **Pest 4.0** (PHPUnit-based). Test files mirror the source structure under `tests/Unit/`. Tests are written with Pest's functional syntax (`test()`, `it()`, `expect()`).
