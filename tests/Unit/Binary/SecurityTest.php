<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\Unit\Binary;

use PHPUnit\Framework\TestCase;
use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\Socket;
use PrettyPhp\Binary\Security\SecurityConfig;
use PrettyPhp\Binary\Security\SecurityException;
use PrettyPhp\Binary\Security\BufferOverflowException;
use PrettyPhp\Binary\Security\RateLimitException;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\Security\SecurityAudit;

class SimplePacket
{
    #[Binary('8')]
    public int $type = 0;

    #[Binary('8')]
    public int $code = 0;
}

class NestedPacket
{
    #[Binary('8')]
    public int $version = 1;

    #[Binary(SimplePacket::class)]
    public SimplePacket $nested;

    public function __construct()
    {
        $this->nested = new SimplePacket();
    }
}

class DeepNestedPacket
{
    #[Binary('8')]
    public int $level = 0;

    #[Binary(NestedPacket::class)]
    public NestedPacket $nested;

    public function __construct()
    {
        $this->nested = new NestedPacket();
    }
}

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset security config before each test
        SecurityConfig::reset();
    }

    protected function tearDown(): void
    {
        // Reset security config after each test
        SecurityConfig::reset();
        parent::tearDown();
    }

    public function testSecurityConfigMaxBufferSize(): void
    {
        $this->assertSame(SecurityConfig::DEFAULT_MAX_BUFFER_SIZE, SecurityConfig::getMaxBufferSize());

        SecurityConfig::setMaxBufferSize(1024);
        $this->assertSame(1024, SecurityConfig::getMaxBufferSize());
    }

    public function testSecurityConfigInvalidMaxBufferSize(): void
    {
        $this->expectException(SecurityException::class);
        SecurityConfig::setMaxBufferSize(0);
    }

    public function testSecurityConfigMaxNestingDepth(): void
    {
        $this->assertSame(SecurityConfig::DEFAULT_MAX_NESTING_DEPTH, SecurityConfig::getMaxNestingDepth());

        SecurityConfig::setMaxNestingDepth(50);
        $this->assertSame(50, SecurityConfig::getMaxNestingDepth());
    }

    public function testSecurityConfigInvalidMaxNestingDepth(): void
    {
        $this->expectException(SecurityException::class);
        SecurityConfig::setMaxNestingDepth(-1);
    }

    public function testSecurityConfigStrictMode(): void
    {
        $this->assertFalse(SecurityConfig::isStrictMode());

        SecurityConfig::enableStrictMode();
        $this->assertTrue(SecurityConfig::isStrictMode());

        SecurityConfig::disableStrictMode();
        $this->assertFalse(SecurityConfig::isStrictMode());
    }

    public function testBufferOverflowProtectionOnUnpack(): void
    {
        // Set a very small buffer size
        SecurityConfig::setMaxBufferSize(10);

        // Create data larger than the limit
        $largeData = str_repeat("\x00", 100);

        $this->expectException(BufferOverflowException::class);
        $this->expectExceptionMessage('Buffer overflow protection');

        Binary::unpack($largeData, SimplePacket::class);
    }

    public function testBufferOverflowProtectionAllowsSmallData(): void
    {
        SecurityConfig::setMaxBufferSize(100);

        $packet = new SimplePacket();
        $packet->type = 8;
        $packet->code = 0;

        $packed = Binary::pack($packet);
        $unpacked = Binary::unpack($packed, SimplePacket::class);

        $this->assertEquals(8, $unpacked->type);
        $this->assertEquals(0, $unpacked->code);
    }

    public function testNestingDepthProtection(): void
    {
        // Set a low nesting depth
        SecurityConfig::setMaxNestingDepth(1);

        $packet = new DeepNestedPacket();
        $packed = Binary::pack($packet);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Maximum nesting depth exceeded');

        Binary::unpack($packed, DeepNestedPacket::class);
    }

    public function testNestingDepthAllowsNormalNesting(): void
    {
        SecurityConfig::setMaxNestingDepth(10);

        $packet = new NestedPacket();
        $packet->version = 2;
        $packet->nested->type = 8;

        $packed = Binary::pack($packet);
        $unpacked = Binary::unpack($packed, NestedPacket::class);

        $this->assertEquals(2, $unpacked->version);
        $this->assertEquals(8, $unpacked->nested->type);
    }

    public function testRateLimiterDefault(): void
    {
        $limiter = RateLimiter::default();
        $this->assertInstanceOf(RateLimiter::class, $limiter);
        $this->assertTrue($limiter->isEnabled());
    }

    public function testRateLimiterStrict(): void
    {
        $limiter = RateLimiter::strict();
        $this->assertInstanceOf(RateLimiter::class, $limiter);
    }

    public function testRateLimiterPermissive(): void
    {
        $limiter = RateLimiter::permissive();
        $this->assertInstanceOf(RateLimiter::class, $limiter);
    }

    public function testRateLimiterCheckLimit(): void
    {
        $limiter = new RateLimiter(5, 60); // 5 requests per minute

        // Should allow first 5 requests
        for ($i = 0; $i < 5; $i++) {
            $limiter->checkLimit('test');
            $this->assertTrue(true); // No exception thrown
        }

        // 6th request should fail
        $this->expectException(RateLimitException::class);
        $limiter->checkLimit('test');
    }

    public function testRateLimiterTryOperation(): void
    {
        $limiter = new RateLimiter(3, 60);

        // First 3 should succeed
        $this->assertTrue($limiter->tryOperation());
        $this->assertTrue($limiter->tryOperation());
        $this->assertTrue($limiter->tryOperation());

        // 4th should fail
        $this->assertFalse($limiter->tryOperation());
    }

    public function testRateLimiterGetRemainingTokens(): void
    {
        $limiter = new RateLimiter(10, 60);
        $this->assertSame(10, $limiter->getRemainingTokens());

        $limiter->tryOperation();
        $this->assertSame(9, $limiter->getRemainingTokens());
    }

    public function testRateLimiterReset(): void
    {
        $limiter = new RateLimiter(5, 60);

        // Use all tokens
        for ($i = 0; $i < 5; $i++) {
            $limiter->tryOperation();
        }

        $this->assertSame(0, $limiter->getRemainingTokens());

        // Reset should restore all tokens
        $limiter->reset();
        $this->assertSame(5, $limiter->getRemainingTokens());
    }

    public function testRateLimiterDisable(): void
    {
        $limiter = new RateLimiter(2, 60);

        // Use all tokens
        $limiter->tryOperation();
        $limiter->tryOperation();
        $this->assertFalse($limiter->tryOperation());

        // Disable rate limiting
        $limiter->disable();
        $this->assertFalse($limiter->isEnabled());

        // Should now allow unlimited operations
        $this->assertTrue($limiter->tryOperation());
        $this->assertTrue($limiter->tryOperation());
    }

    public function testSocketRateLimiterIntegration(): void
    {
        $socket = Socket::udp();
        $this->assertNotInstanceOf(\PrettyPhp\Binary\Security\RateLimiter::class, $socket->getRateLimiter());

        $limiter = RateLimiter::default();
        $socket->setRateLimiter($limiter);

        $this->assertSame($limiter, $socket->getRateLimiter());
    }

    public function testSecurityAuditBinaryStructure(): void
    {
        $audit = new SecurityAudit();
        $findings = $audit->auditBinaryStructure(SimplePacket::class);

        $this->assertIsArray($findings);
    }

    public function testSecurityAuditSocket(): void
    {
        $socket = Socket::udp();
        $audit = new SecurityAudit();
        $findings = $audit->auditSocket($socket);

        $this->assertIsArray($findings);
        $this->assertNotEmpty($findings);
        $hasWarning = array_any($findings, fn($finding): bool => $finding['severity'] === 'warning' &&
            str_contains($finding['message'], 'rate limiting'));
        $this->assertTrue($hasWarning);
    }

    public function testSecurityAuditSocketWithRateLimiter(): void
    {
        $socket = Socket::udp();
        $socket->setRateLimiter(RateLimiter::default());

        $audit = new SecurityAudit();
        $findings = $audit->auditSocket($socket);

        $this->assertIsArray($findings);
        // Should not have warning about missing rate limiter
    }

    public function testSecurityAuditValidateConfiguration(): void
    {
        $findings = SecurityAudit::validateConfiguration();
        $this->assertIsArray($findings);
        $this->assertNotEmpty($findings);
    }

    public function testSecurityAuditGenerateReport(): void
    {
        $report = SecurityAudit::generateReport();
        $this->assertIsString($report);
        $this->assertStringContainsString('Security Audit Report', $report);
        $this->assertStringContainsString('Configuration', $report);
        $this->assertStringContainsString('Recommendations', $report);
    }

    public function testBufferOverflowExceptionMessage(): void
    {
        $exception = new BufferOverflowException(1000, 500);
        $message = $exception->getMessage();

        $this->assertStringContainsString('1000', $message);
        $this->assertStringContainsString('500', $message);
        $this->assertStringContainsString('Buffer overflow protection', $message);
    }

    public function testRateLimitExceptionMessage(): void
    {
        $exception = new RateLimitException('test operation', 100, 60);
        $message = $exception->getMessage();

        $this->assertStringContainsString('test operation', $message);
        $this->assertStringContainsString('100', $message);
        $this->assertStringContainsString('60', $message);
    }

    public function testSecurityConfigReset(): void
    {
        SecurityConfig::setMaxBufferSize(1024);
        SecurityConfig::setMaxNestingDepth(10);
        SecurityConfig::enableStrictMode();

        SecurityConfig::reset();

        $this->assertSame(SecurityConfig::DEFAULT_MAX_BUFFER_SIZE, SecurityConfig::getMaxBufferSize());
        $this->assertSame(SecurityConfig::DEFAULT_MAX_NESTING_DEPTH, SecurityConfig::getMaxNestingDepth());
        $this->assertFalse(SecurityConfig::isStrictMode());
    }

    public function testInsufficientDataThrowsBufferOverflow(): void
    {
        // Create incomplete data (only 1 byte when 2 are needed)
        $incompleteData = "\x08";

        $this->expectException(BufferOverflowException::class);
        Binary::unpack($incompleteData, SimplePacket::class);
    }

    public function testRateLimiterInvalidMaxRequests(): void
    {
        $this->expectException(SecurityException::class);
        new RateLimiter(0, 60);
    }

    public function testRateLimiterInvalidWindowSeconds(): void
    {
        $this->expectException(SecurityException::class);
        new RateLimiter(100, 0);
    }

    public function testRateLimiterTimeUntilNextToken(): void
    {
        $limiter = new RateLimiter(1, 60);

        // Use the only token
        $limiter->tryOperation();

        // Check time until next token
        $waitTime = $limiter->getTimeUntilNextToken();
        $this->assertGreaterThan(0, $waitTime);
        $this->assertLessThanOrEqual(60, $waitTime);
    }

    public function testRateLimiterTokenRefill(): void
    {
        $limiter = new RateLimiter(10, 1); // 10 requests per second

        // Use some tokens
        $limiter->tryOperation();
        $limiter->tryOperation();

        $this->assertSame(8, $limiter->getRemainingTokens());

        // Wait for refill (1 second)
        sleep(1);

        // Tokens should be refilled
        $this->assertSame(10, $limiter->getRemainingTokens());
    }
}
