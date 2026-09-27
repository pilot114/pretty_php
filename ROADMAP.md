# Pretty PHP Roadmap

This document outlines the development roadmap for Pretty PHP.

## Current Status (pre-release, 0.x)

✅ Core base wrappers: `Str`, `Arr`, `File`, `Path`, `Num`, `Json`  
✅ Date & time: `DateTime`, `Date`, `DateInterval`, `Timezone`  
✅ Sessions: `Session` with pluggable storage (`NativeSessionStorage`, `ArraySessionStorage`)  
✅ Binary system with network protocols (IP, ICMP, TCP, UDP, ARP, DNS, HTTP)  
✅ Advanced binary features (BitField, Conditional packing, Validation, docs & ASCII diagram generation)  
✅ Network utilities (Socket, RawSocket, NetworkInterface, PacketCapture)  
✅ Security system (buffer overflow protection, rate limiting, security audit)  
✅ Functional types: `Result`, `Option`, `TryResult`  
✅ Curl wrapper (`Curl`, `CurlHandle`, `CurlMultiHandle`, `CurlShareHandle`)  
✅ POSIX wrappers (`Posix`, `PosixProcess`, `PosixUser`, `PosixFile`, `PosixSystem`)  
✅ 1200+ tests, 100% line coverage  
✅ PHPStan max level, Rector, PHPCS (PSR-12)  
✅ Zero runtime dependencies  

## Phase 1: Foundation & Release

**Status**: 🚧 Almost complete

### Documentation & Release Infrastructure
- [x] Create README.md
- [x] Create ROADMAP.md
- [x] Setup GitHub Actions CI/CD
- [x] Add badges (version, downloads, coverage, build status)
- [ ] Publish to Packagist (requires manual setup - see notes below)
- [ ] Create first tagged GitHub release (automated via release.yml, no tags exist yet)

### Code Quality
- [x] Translate Russian comments to English
- [x] Reach 100% PHPStan compliance
- [x] Add coverage reporting
- [x] Setup automated dependency updates
- [x] Reach 100% line coverage

### Notes
**Packagist Setup**: To publish the package, visit [packagist.org](https://packagist.org/packages/submit), submit the GitHub repository URL, and configure the webhook. Then add `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` secrets to GitHub repository settings.

**Creating Releases**: Push a version tag (e.g., `git tag -a v0.1.0 -m "Release v0.1.0" && git push origin v0.1.0`) to automatically create a GitHub release and trigger Packagist update.

## Phase 2: Maintenance & Consistency

**Status**: 🚧 In Progress

### PHP 8.5 Alignment
- [x] CI workflows (`ci.yml`, `coverage.yml`, `release.yml`) run on PHP 8.5
- [x] PHPStan `phpVersion: 80500`
- [x] Rector `LevelSetList::UP_TO_PHP_85` / `withPhpSets(php85: true)`
- [x] README, CLAUDE.md and GitHub templates updated to PHP 8.5
- [x] No deprecation notices in the test suite: `Date::RFC7231` no longer reads the deprecated native constant
  (and is marked `#[\Deprecated]`), deprecated `Date::sunrise()`/`Date::sunset()` wrappers removed
  in favour of `Date::sunInfo()` / `DateTime::sunrise()` / `DateTime::sunset()`

### Package Metadata
- [ ] Package name mismatch: `composer.json` uses `prettyph/pretty-php`, README badges point to `pilot114/pretty_php`

### Known Issues
- [x] `Binary::unpack()` of a nested structure computed the nested size without bit field bytes and with
  skipped conditional fields, so following fields were read at a wrong offset
- [x] `Binary::generateAsciiDiagram()` rendered rows of different widths; fields wider than 32 bits
  (64-bit integers, long fixed strings) now span several rows
- [x] `RawSocket` used `IP_HDRINCL = 2` (which is `IP_TTL` on Linux)
- [ ] `PacketCapture` with protocol `0` opens an `AF_INET` raw socket with protocol `0x0003` (ETH_P_ALL),
  which only makes sense for `AF_PACKET` sockets. Needs a design decision: capturing all traffic requires
  `AF_PACKET` and yields Ethernet frames, so `CapturedPacket::parse*()` would have to skip the link-layer header

### Quality Gates
- [x] Enforce 100% coverage in CI (`pest --coverage --min=100`)
- [x] Mutation testing with Pest (`composer mutate`, minimum score 85%, current 87.6%, weekly/PR CI job)
- [ ] Root-only code paths (raw sockets, IP_HDRINCL, `initgroups`) are excluded from coverage with
  `@codeCoverageIgnore`; consider a privileged CI job to exercise them

## Phase 3: Developer Experience

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
- [x] Result type (Ok/Err) for railway-oriented programming
- [x] Option type (Some/None) for null-safety
- [x] Try type (`TryResult`) for exception handling

### Developer Tooling
- [ ] PHPStorm plugin for autocomplete
- [ ] Rector rules for migration from native PHP
- [ ] Code generators for Binary structures
- [ ] CLI tool for common operations

## Phase 4: Performance & Optimization

**Status**: 📋 Planned

### Performance Improvements
- [ ] Profile with Blackfire/XHProf
- [ ] Optimize hot paths
- [ ] Implement caching for expensive operations
- [ ] Memory optimization for large files
- [ ] Lazy evaluation where applicable

### Benchmarking
- [x] Initial benchmark suite (`Str`, `Arr`, `File`, `Json`, `Num`, `Path`)
- [x] Expand benchmark suite to DateTime, Binary, Curl modules
- [ ] Compare with alternatives (Laravel Collections, Symfony String)
- [ ] Continuous benchmarking in CI
- [ ] Performance regression detection

### Alternative Implementations
- [ ] FFI bindings for critical operations
- [ ] Use native extensions where beneficial
- [ ] Parallel processing support

## Phase 5: Ecosystem & Community

**Status**: 💡 Future

### Framework Integrations
- [ ] Laravel package
- [ ] Symfony bundle
- [ ] Composer plugin
- [ ] PHPStan extensions for better type inference

### Additional Packages
- [ ] `pretty-php/http` - high-level HTTP client (PSR-18) built on top of the Curl module
- [ ] `pretty-php/database` - Database query builder
- [ ] `pretty-php/cache` - Cache wrapper
- [ ] `pretty-php/async` - Async/await patterns

### Community Building
- [x] Code of Conduct and CONTRIBUTING guide
- [ ] Regular release schedule
- [ ] Create Discord/Slack community
- [ ] Blog with updates and tutorials

## Phase 6: Stabilization & v1.0

**Status**: 💡 Future

### Pre-v1.0 Checklist
- [ ] API freeze
- [x] 100% code coverage
- [ ] Security audit
- [ ] Performance benchmarks vs competitors
- [ ] Complete documentation review (only `docs/DATE_MODULE.md` exists so far)
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
