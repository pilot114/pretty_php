# Pretty PHP Roadmap

This document outlines the planned development roadmap for Pretty PHP.

## Current Status (v0.2.0)

✅ Core base wrappers (Str, Arr, File, Path, Num, Json, DateTime)
✅ Extended binary system with multiple network protocols (IP, ICMP, TCP, UDP, ARP, DNS, HTTP)
✅ Advanced binary features (BitField, Conditional packing, Validation)
✅ Network utilities (Socket, RawSocket, NetworkInterface, PacketCapture)
✅ Security system (Buffer overflow protection, Rate limiting, Security audit)
✅ Functional programming types (Result, Option)
✅ Comprehensive test coverage (700+ tests)
✅ Full type safety with PHPStan max level
✅ Zero dependencies

## Phase 1: Foundation & Release (Q1 2025)

**Status**: ✅ Complete

### Documentation & Release Infrastructure
- [x] Create README.md
- [x] Create ROADMAP.md
- [x] Setup GitHub Actions CI/CD
- [x] Add badges (version, downloads, coverage, build status)
- [ ] Publish to Packagist (requires manual setup - see notes below)
- [ ] Create GitHub releases with release notes (automated via release.yml)

### Code Quality
- [x] Translate Russian comments to English
- [x] Reach 100% PHPStan compliance
- [x] Add coverage reporting
- [x] Setup automated dependency updates

### Notes
**Packagist Setup**: To publish the package, visit [packagist.org](https://packagist.org/packages/submit), submit the GitHub repository URL, and configure the webhook. Then add `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` secrets to GitHub repository settings.

**Creating Releases**: Push a version tag (e.g., `git tag -a v0.1.0 -m "Release v0.1.0" && git push origin v0.1.0`) to automatically create a GitHub release and trigger Packagist update.



## Phase 2: Expand Base Components (Q2 2025)

**Status**: ✅ Complete

### New Base Classes

#### Num - Numeric Operations
- [x] Number formatting (thousands separator, decimals)
- [x] Math operations (round, floor, ceil, abs, clamp)
- [x] Validation (isEven, isOdd, isPrime, inRange)
- [x] Base conversion (hex, binary, octal)
- [x] Currency formatting

#### Json - JSON Operations
- [x] Safe encode/decode with error handling
- [x] JSON validation
- [x] JSON Path queries
- [x] Pretty printing
- [ ] JSON Schema validation

#### DateTime - Date/Time Operations
- [x] Fluent API wrapper for DateTimeImmutable
- [x] Convenient formatters
- [x] Interval operations
- [x] Timezone handling
- [x] Relative time (ago, until)

#### Xml - XML Operations
- [ ] Parse and generate XML
- [ ] XPath queries
- [ ] XML ↔ Array conversion
- [ ] Namespace handling

### Enhanced Existing Classes

#### Str Enhancements
- [ ] Unicode normalization
- [ ] Slug generation
- [ ] Truncate with ellipsis
- [ ] Case conversions (snake_case, camelCase, PascalCase, kebab-case)
- [ ] Levenshtein distance
- [ ] String similarity

#### Arr Enhancements
- [ ] `groupBy()`, `partition()`
- [ ] `zip()`, `unzip()`
- [ ] `difference()`, `intersection()`, `union()`
- [ ] `flatten()` with depth control
- [ ] Multi-key sorting
- [ ] `pluck()` for extracting values

#### File Enhancements
- [ ] Atomic writes (write to temp + rename)
- [ ] File locking mechanisms
- [ ] Stream handling for large files
- [ ] Improved MIME type detection
- [ ] Hash calculation (md5, sha256, etc.)
- [ ] Temporary file handling

#### Path Enhancements
- [ ] Improved Windows path support
- [ ] Symbolic link handling
- [ ] Path validation
- [ ] URL path operations
- [ ] Recursive glob patterns

## Phase 3: Binary System Expansion (Q3 2025)

**Status**: ✅ Complete

### New Network Protocols
- [x] TCPPacket - TCP packets with checksum
- [x] UDPPacket - UDP packets
- [x] ARPPacket - ARP protocol
- [x] DNSPacket - DNS queries/responses
- [x] HTTPPacket - Basic HTTP packets

### Binary System Improvements
- [x] Big-endian/little-endian support via attributes
- [x] Bit field support (not just bytes)
- [x] Conditional packing (if/else in structures)
- [x] Validation on unpack
- [ ] Auto-generate structure documentation

### Network Utilities
- [x] Socket - Wrapper for socket operations
- [x] NetworkInterface - Network interface information
- [x] PacketCapture - Basic packet sniffer
- [x] RawSocket - Enhanced raw socket handling

### Security
- [x] Security audit of binary operations
- [x] Buffer overflow protection
- [x] Rate limiting for network operations
- [x] Security documentation for raw sockets

## Phase 4: Developer Experience (Q4 2025)

**Status**: 🚧 In Progress

### Collections & Pipeline
- [ ] Collection - Advanced collection with lazy evaluation
- [ ] Pipeline - Functional composition API
- [ ] Immutable/mutable mode toggle
- [ ] Method chaining optimizations

### Validation & Type Safety
- [ ] Validator class for data validation
- [ ] Fluent validation API
- [ ] Built-in validation rules
- [ ] Custom validator support
- [x] Result type (Ok/Error) for railway-oriented programming
- [x] Option type (Some/None) for null-safety
- [ ] Try type for exception handling

### Developer Tooling
- [ ] PHPStorm plugin for autocomplete
- [ ] Rector rules for migration from native PHP
- [ ] Code generators for Binary structures
- [ ] CLI tool for common operations

## Phase 5: Performance & Optimization (Q1 2026)

**Status**: 📋 Planned

### Performance Improvements
- [ ] Profile with Blackfire/XHProf
- [ ] Optimize hot paths
- [ ] Implement caching for expensive operations
- [ ] Memory optimization for large files
- [ ] Lazy evaluation where applicable

### Benchmarking
- [ ] Expand benchmark suite
- [ ] Compare with alternatives (Laravel Collections, Symfony String)
- [ ] Continuous benchmarking in CI
- [ ] Performance regression detection

### Alternative Implementations
- [ ] FFI bindings for critical operations
- [ ] Use native extensions where beneficial
- [ ] Parallel processing support

## Phase 6: Ecosystem & Community (2026+)

**Status**: 💡 Future

### Framework Integrations
- [ ] Laravel package
- [ ] Symfony bundle
- [ ] Composer plugin
- [ ] PHPStan extensions for better type inference

### Additional Packages
- [ ] `pretty-php/http` - HTTP client
- [ ] `pretty-php/database` - Database query builder
- [ ] `pretty-php/cache` - Cache wrapper
- [ ] `pretty-php/async` - Async/await patterns

### Community Building
- [ ] Create Discord/Slack community
- [ ] Code of Conduct
- [ ] Regular release schedule
- [ ] Blog with updates and tutorials
- [ ] Video tutorials
- [ ] Conference talks

## Phase 7: Stabilization & v1.0 (TBD)

**Status**: 💡 Future

### Pre-v1.0 Checklist
- [ ] API freeze
- [ ] 100% code coverage
- [ ] Security audit
- [ ] Performance benchmarks vs competitors
- [ ] Complete documentation review
- [ ] Beta testing period (3+ months)
- [ ] Migration guides from 0.x

### v1.0 Release
- [ ] Semantic versioning commitment
- [ ] Backward compatibility promise
- [ ] LTS support plan (2+ years)
- [ ] Professional documentation site
- [ ] Comprehensive examples repository

## Feedback

Have ideas for the roadmap? Open an issue or discussion on GitHub!

---

**Legend:**
- ✅ Done
- 🚧 In Progress
- 📋 Planned
- 💡 Future/Ideas
