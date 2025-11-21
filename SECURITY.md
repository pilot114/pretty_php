# Security Guide for Pretty PHP

This document provides security guidelines for using Pretty PHP's binary operations and network features.

## Table of Contents

1. [Overview](#overview)
2. [Buffer Overflow Protection](#buffer-overflow-protection)
3. [Rate Limiting](#rate-limiting)
4. [Raw Socket Security](#raw-socket-security)
5. [Security Best Practices](#security-best-practices)
6. [Security Configuration](#security-configuration)
7. [Security Auditing](#security-auditing)

## Overview

Pretty PHP includes several security features to protect against common vulnerabilities when working with binary data and network operations:

- **Buffer overflow protection**: Prevents processing of oversized binary data
- **Rate limiting**: Protects against abuse of network operations
- **Security auditing**: Tools to analyze code for potential security issues
- **Nesting depth limits**: Prevents stack overflow from deeply nested structures

## Buffer Overflow Protection

### What is Buffer Overflow Protection?

Buffer overflow attacks occur when an application processes more data than allocated, potentially leading to crashes, memory corruption, or security vulnerabilities. Pretty PHP's Binary system includes automatic protection against these attacks.

### How It Works

When unpacking binary data, Pretty PHP automatically:

1. **Validates total buffer size**: Ensures incoming data doesn't exceed configured maximum
2. **Checks expected vs available data**: Verifies sufficient data exists before unpacking each field
3. **Limits nesting depth**: Prevents stack overflow from recursive structures

### Configuration

```php
use PrettyPhp\Binary\Security\SecurityConfig;

// Set maximum buffer size (default: 10MB)
SecurityConfig::setMaxBufferSize(5 * 1024 * 1024); // 5MB

// Set maximum nesting depth (default: 100)
SecurityConfig::setMaxNestingDepth(50);
```

### Example

```php
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\Security\BufferOverflowException;

try {
    $packet = Binary::unpack($untrustedData, MyPacket::class);
} catch (BufferOverflowException $e) {
    // Handle oversized data
    error_log("Buffer overflow attempt detected: " . $e->getMessage());
}
```

## Rate Limiting

### Why Rate Limiting?

Rate limiting prevents:
- **Denial of Service (DoS) attacks**: Limit resource consumption
- **Network abuse**: Prevent excessive packet sending
- **Resource exhaustion**: Protect against memory/CPU exhaustion

### Implementation

```php
use PrettyPhp\Binary\Socket;
use PrettyPhp\Binary\Security\RateLimiter;

// Create a socket with rate limiting
$socket = Socket::udp();
$socket->setRateLimiter(RateLimiter::default()); // 1000 req/min

// Or use strict limits
$socket->setRateLimiter(RateLimiter::strict()); // 100 req/min

// Custom rate limits
$socket->setRateLimiter(new RateLimiter(
    maxRequests: 500,
    windowSeconds: 60
));
```

### Handling Rate Limit Exceptions

```php
use PrettyPhp\Binary\Security\RateLimitException;

try {
    $socket->sendTo($data, $address, $port);
} catch (RateLimitException $e) {
    // Rate limit exceeded
    $rateLimiter = $socket->getRateLimiter();
    $waitTime = $rateLimiter->getTimeUntilNextToken();

    error_log("Rate limit exceeded. Wait {$waitTime}s before retrying");
}
```

### Disabling Rate Limiting (Testing Only)

```php
// For testing purposes only - DO NOT use in production
$rateLimiter = RateLimiter::default();
$rateLimiter->disable();
$socket->setRateLimiter($rateLimiter);
```

## Raw Socket Security

### ⚠️ Security Warning

**Raw sockets are inherently dangerous and should be used with extreme caution.**

### Risks of Raw Sockets

1. **Requires root/administrator privileges**: Security risk if compromised
2. **Can craft arbitrary packets**: Potential for network attacks
3. **Bypass firewall rules**: May circumvent security policies
4. **Network sniffing**: Can capture sensitive data
5. **Spoofing attacks**: Can forge source addresses

### Security Requirements

When using raw sockets, you **MUST**:

#### 1. Run with Minimum Privileges

```php
use PrettyPhp\Binary\RawSocket;

// Check if running with appropriate privileges
if (posix_geteuid() !== 0) {
    throw new RuntimeException('Raw sockets require root privileges');
}

// After creating socket, drop privileges if possible
$socket = RawSocket::icmp();

// Drop privileges (pseudocode - implement based on your system)
if (function_exists('posix_setuid')) {
    posix_setuid($unprivilegedUserId);
}
```

#### 2. Implement Rate Limiting

```php
use PrettyPhp\Binary\Security\RateLimiter;

$socket = RawSocket::icmp();
$socket->setRateLimiter(RateLimiter::strict());
```

#### 3. Validate All Input

```php
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\Security\SecurityException;

// Always validate packet data before sending
function validatePacket(object $packet): void {
    // Implement packet validation logic
    if ($packet->type < 0 || $packet->type > 255) {
        throw new SecurityException('Invalid packet type');
    }
}

$packet = new ICMPPacket();
validatePacket($packet);
$socket->sendPacket($packet, $destination);
```

#### 4. Sanitize Destination Addresses

```php
// Prevent attacks on internal networks
function isValidDestination(string $ip): bool {
    // Block private IP ranges
    $privateRanges = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
    ];

    foreach ($privateRanges as $range) {
        if (ipInRange($ip, $range)) {
            return false;
        }
    }

    return true;
}

if (!isValidDestination($destination)) {
    throw new SecurityException('Destination is not allowed');
}
```

#### 5. Implement Logging and Monitoring

```php
// Log all raw socket operations
function logSocketOperation(string $operation, string $destination): void {
    error_log(sprintf(
        '[SECURITY] Raw socket %s to %s by user %d',
        $operation,
        $destination,
        posix_getuid()
    ));
}

logSocketOperation('ICMP ping', $destination);
$socket->sendPacket($packet, $destination);
```

#### 6. Set Timeouts

```php
// Always set timeouts to prevent resource exhaustion
$socket->setReceiveTimeout(5); // 5 seconds
$socket->setSendTimeout(5);
```

### Raw Socket Best Practices

| Practice | Why | How |
|----------|-----|-----|
| **Validate packet size** | Prevent buffer overflow | Check size before pack/unpack |
| **Limit packet rate** | Prevent DoS | Use RateLimiter |
| **Audit all operations** | Security monitoring | Use SecurityAudit |
| **Restrict destinations** | Prevent abuse | Whitelist/blacklist IPs |
| **Drop privileges** | Minimize damage | Use posix_setuid after socket creation |
| **Implement timeouts** | Prevent hanging | Use setReceiveTimeout/setSendTimeout |
| **Log operations** | Forensics/debugging | Log all sends/receives |

### Example: Secure Raw Socket Usage

```php
use PrettyPhp\Binary\RawSocket;
use PrettyPhp\Binary\ICMPPacket;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\Security\SecurityException;

class SecureRawSocketHandler
{
    private RawSocket $socket;

    public function __construct()
    {
        // 1. Check privileges
        if (posix_geteuid() !== 0) {
            throw new SecurityException('Requires root privileges');
        }

        // 2. Create socket
        $this->socket = RawSocket::icmp();

        // 3. Configure security
        $this->socket->setRateLimiter(RateLimiter::strict());
        $this->socket->setReceiveTimeout(5);
        $this->socket->setSendTimeout(5);
    }

    public function ping(string $destination): bool
    {
        // 4. Validate destination
        if (!$this->isValidDestination($destination)) {
            throw new SecurityException('Invalid destination');
        }

        // 5. Log operation
        $this->log('ping', $destination);

        // 6. Create and validate packet
        $packet = new ICMPPacket();
        $packet->type = 8; // Echo request
        $this->validatePacket($packet);

        // 7. Send with rate limiting (handled automatically)
        try {
            $response = $this->socket->sendAndReceive(
                $packet,
                $destination,
                0,
                ICMPPacket::class
            );
            return $response->responseData !== null;
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            return false;
        }
    }

    private function isValidDestination(string $ip): bool
    {
        // Implement IP validation
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
    }

    private function validatePacket(ICMPPacket $packet): void
    {
        if ($packet->type < 0 || $packet->type > 255) {
            throw new SecurityException('Invalid ICMP type');
        }
    }

    private function log(string $operation, string $message): void
    {
        error_log("[RawSocket] {$operation}: {$message}");
    }
}
```

## Security Best Practices

### 1. Always Validate Input

```php
// BAD: No validation
$packet = Binary::unpack($data, Packet::class);

// GOOD: Validate before unpacking
if (strlen($data) > 1500) { // MTU size
    throw new SecurityException('Packet too large');
}
$packet = Binary::unpack($data, Packet::class);
```

### 2. Use Strict Mode in Production

```php
use PrettyPhp\Binary\Security\SecurityConfig;

// Enable strict security mode
SecurityConfig::enableStrictMode();
```

### 3. Implement Error Handling

```php
use PrettyPhp\Binary\Security\SecurityException;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\RateLimitException;

try {
    $socket->sendTo($data, $ip, $port);
} catch (BufferOverflowException $e) {
    // Handle buffer overflow
} catch (RateLimitException $e) {
    // Handle rate limit
} catch (SecurityException $e) {
    // Handle other security issues
}
```

### 4. Regular Security Audits

```php
use PrettyPhp\Binary\Security\SecurityAudit;

// Audit binary structures
$audit = new SecurityAudit();
$findings = $audit->auditBinaryStructure(MyPacket::class);

// Audit sockets
$findings = $audit->auditSocket($socket);

// Generate security report
$report = SecurityAudit::generateReport();
echo $report;
```

## Security Configuration

### Default Configuration

```php
use PrettyPhp\Binary\Security\SecurityConfig;

// Defaults
SecurityConfig::DEFAULT_MAX_BUFFER_SIZE;      // 10MB
SecurityConfig::DEFAULT_MAX_NESTING_DEPTH;    // 100
SecurityConfig::DEFAULT_RATE_LIMIT_REQUESTS;  // 1000
SecurityConfig::DEFAULT_RATE_LIMIT_WINDOW;    // 60 seconds
```

### Recommended Production Configuration

```php
use PrettyPhp\Binary\Security\SecurityConfig;

// Production settings
SecurityConfig::setMaxBufferSize(1 * 1024 * 1024); // 1MB
SecurityConfig::setMaxNestingDepth(50);
SecurityConfig::enableStrictMode();
```

### Configuration for Different Use Cases

#### High-Security Environment

```php
SecurityConfig::setMaxBufferSize(512 * 1024); // 512KB
SecurityConfig::setMaxNestingDepth(20);
SecurityConfig::enableStrictMode();

$socket->setRateLimiter(RateLimiter::strict()); // 100 req/min
```

#### Development/Testing

```php
SecurityConfig::setMaxBufferSize(100 * 1024 * 1024); // 100MB
SecurityConfig::setMaxNestingDepth(1000);
SecurityConfig::disableStrictMode();

// Optional: Disable rate limiting for tests
$rateLimiter = RateLimiter::default();
$rateLimiter->disable();
```

## Security Auditing

### Auditing Binary Structures

```php
use PrettyPhp\Binary\Security\SecurityAudit;

$audit = new SecurityAudit();
$findings = $audit->auditBinaryStructure(MyComplexPacket::class);

foreach ($findings as $finding) {
    echo "[{$finding['severity']}] {$finding['message']}\n";
    echo "  → {$finding['recommendation']}\n";
}
```

### Auditing Sockets

```php
$audit = new SecurityAudit();
$findings = $audit->auditSocket($mySocket);

if (!empty($findings)) {
    // Security issues found
    foreach ($findings as $finding) {
        if ($finding['severity'] === 'critical') {
            throw new SecurityException($finding['message']);
        }
    }
}
```

### Validating Configuration

```php
$findings = SecurityAudit::validateConfiguration();

foreach ($findings as $finding) {
    if ($finding['severity'] === 'warning') {
        error_log("Security warning: " . $finding['message']);
    }
}
```

### Generating Reports

```php
// Generate and save security report
$report = SecurityAudit::generateReport();
file_put_contents('/var/log/security-audit.txt', $report);

// Or display it
echo $report;
```

## Threat Model

### Threats Mitigated

| Threat | Mitigation | Implementation |
|--------|-----------|----------------|
| Buffer overflow | Size validation | BufferOverflowException |
| Stack overflow | Nesting depth limits | SecurityConfig::setMaxNestingDepth() |
| DoS attacks | Rate limiting | RateLimiter |
| Resource exhaustion | Buffer size limits | SecurityConfig::setMaxBufferSize() |
| Malformed packets | Validation attributes | Validate attribute |

### Threats NOT Mitigated

These require additional application-level security:

- **Authentication**: Verify sender identity
- **Encryption**: Protect data confidentiality
- **Integrity**: Detect tampering (use checksums/MACs)
- **Replay attacks**: Implement nonce/timestamp validation
- **Man-in-the-middle**: Use encrypted channels

## Reporting Security Issues

If you discover a security vulnerability in Pretty PHP, please:

1. **Do NOT** open a public issue
2. Email the maintainer privately with details
3. Allow reasonable time for a fix before disclosure
4. Follow responsible disclosure practices

## Security Checklist

Before deploying to production:

- [ ] Configured appropriate buffer size limits
- [ ] Set nesting depth limits
- [ ] Enabled strict mode
- [ ] Implemented rate limiting on all sockets
- [ ] Validated all input data
- [ ] Restricted raw socket usage to necessary operations only
- [ ] Implemented proper error handling
- [ ] Added logging for security events
- [ ] Ran security audit on all binary structures
- [ ] Reviewed and restricted destination addresses
- [ ] Set appropriate timeouts
- [ ] Dropped privileges after socket creation (if using raw sockets)
- [ ] Tested with malformed/oversized input
- [ ] Documented security assumptions

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE-120: Buffer Overflow](https://cwe.mitre.org/data/definitions/120.html)
- [CWE-400: Resource Exhaustion](https://cwe.mitre.org/data/definitions/400.html)
- [RFC 792: ICMP Protocol](https://tools.ietf.org/html/rfc792)

---

**Remember**: Security is not a feature, it's a process. Regularly review and update your security measures.
