# Contributing to Pretty PHP

Thanks for your interest in improving Pretty PHP! This guide explains how to set up the project and what is expected from a pull request.

## Requirements

- PHP 8.5+
- Extensions: `ext-ctype`, `ext-curl`, `ext-fileinfo`, `ext-posix`, `ext-sockets`
- Xdebug (only for coverage reports)
- Linux is recommended: some tests use POSIX features (`/dev/ptmx`, FIFOs, file permissions)

## Setup

```bash
git clone https://github.com/pilot114/pretty_php.git
cd pretty_php
composer install
```

## Workflow

1. Create a branch from `main`.
2. Make your change together with tests.
3. Run the checks below; all of them must pass.
4. Open a pull request and fill in the template.

## Checks

```bash
composer test      # Pest test suite
composer coverage  # Tests with coverage (requires Xdebug)
composer check     # PHPStan (level max) + Rector dry-run + PHPCS
composer fix       # Apply Rector and PHPCBF fixes
composer bench     # PHPBench benchmarks
composer mutate    # Mutation testing (requires Xdebug, minimum score 85%)
```

## Coding Guidelines

- **Immutability**: base wrappers are `readonly`; operations return new instances.
- **Fluent API**: return `self`/`static` to allow chaining.
- **Types**: full native types plus PHPDoc generics (`@template`) where needed; PHPStan runs at level `max`.
- **Exceptions**: document thrown exceptions with `@throws` (checked exception tracking is enabled).
- **Style**: PSR-12, 120 characters soft limit, 150 hard limit.
- **Dependencies**: the library has zero runtime dependencies; do not add new ones.
- Do not suppress PHPStan errors with `@phpstan-ignore` or baseline entries; fix the underlying types instead.

## Tests

- Tests use [Pest](https://pestphp.com) functional syntax (`describe()`, `it()`, `expect()`).
- Test files mirror the source layout under `tests/Unit/`; helper classes live in `tests/Support/`.
- Line coverage is kept at **100%** and enforced in CI.
- Mutation testing (`composer mutate`) must stay above 85%. Declare tested classes with `mutates(...)` at the top
  of each test file and prefer exact assertions (see "Mutation Testing" in [AGENTS.md](AGENTS.md)).
- Tests must not require network access or root privileges. Use local resources instead
  (`file://` URLs, the built-in PHP server, UDP sockets on `127.0.0.1`).
- Code that genuinely cannot run in the test process (root-only syscalls, failure branches that
  the OS never triggers) may be marked with `// @codeCoverageIgnore`; explain why in a comment.

## Commit Messages

Use short imperative subject lines (e.g. `Fix nested BitField size calculation`) and describe the
motivation in the body when it is not obvious.

## Reporting Issues

- Bugs and feature requests: use the GitHub issue templates.
- Security vulnerabilities: follow [SECURITY.md](SECURITY.md) and do not open a public issue.

By participating in this project you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
