# Pretty PHP Roadmap

This document outlines the planned development roadmap for Pretty PHP.

## Current Status (v0.1.0)

✅ Core base wrappers (Str, Arr, File, Path)
✅ Binary system with IP/ICMP packets
✅ Comprehensive test coverage (130%+)
✅ Full type safety with PHPStan max level
✅ Zero dependencies

## Phase 1: Foundation & Release (Q1 2025)

**Status**: 🚧 In Progress

### Documentation & Release Infrastructure
- [x] Create README.md
- [x] Create ROADMAP.md
- [ ] Setup GitHub Actions CI/CD
- [ ] Publish to Packagist
- [ ] Create GitHub releases with release notes
- [ ] Add badges (version, downloads, coverage, build status)

### Code Quality
- [x] Translate Russian comments to English
- [ ] Reach 100% PHPStan compliance
- [ ] Add coverage reporting
- [ ] Setup automated dependency updates

## Phase 2: Expand Base Components (Q2 2025)

**Status**: 📋 Planned

### New Base Classes

#### Num - Numeric Operations
- [ ] Number formatting (thousands separator, decimals)
- [ ] Math operations (round, floor, ceil, abs, clamp)
- [ ] Validation (isEven, isOdd, isPrime, inRange)
- [ ] Base conversion (hex, binary, octal)
- [ ] Currency formatting

#### Json - JSON Operations
- [ ] Safe encode/decode with error handling
- [ ] JSON validation
- [ ] JSON Path queries
- [ ] Pretty printing
- [ ] JSON Schema validation

#### DateTime - Date/Time Operations
- [ ] Fluent API wrapper for DateTimeImmutable
- [ ] Convenient formatters
- [ ] Interval operations
- [ ] Timezone handling
- [ ] Relative time (ago, until)

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

**Status**: 📋 Planned

### New Network Protocols
- [ ] TCPPacket - TCP packets with checksum
- [ ] UDPPacket - UDP packets
- [ ] ARPPacket - ARP protocol
- [ ] DNSPacket - DNS queries/responses
- [ ] HTTPPacket - Basic HTTP packets

### Binary System Improvements
- [ ] Big-endian/little-endian support via attributes
- [ ] Bit field support (not just bytes)
- [ ] Conditional packing (if/else in structures)
- [ ] Validation on unpack
- [ ] Auto-generate structure documentation

### Network Utilities
- [ ] Socket - Wrapper for socket operations
- [ ] NetworkInterface - Network interface information
- [ ] PacketCapture - Basic packet sniffer
- [ ] RawSocket - Enhanced raw socket handling

### Security
- [ ] Security audit of binary operations
- [ ] Buffer overflow protection
- [ ] Rate limiting for network operations
- [ ] Security documentation for raw sockets

## Phase 4: Developer Experience (Q4 2025)

**Status**: 📋 Planned

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
- [ ] Result type (Ok/Error) for railway-oriented programming
- [ ] Option type (Some/None) for null-safety
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

## Contributing

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Feedback

Have ideas for the roadmap? Open an issue or discussion on GitHub!

---

**Legend:**
- ✅ Done
- 🚧 In Progress
- 📋 Planned
- 💡 Future/Ideas
