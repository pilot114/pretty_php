<?php

declare(strict_types=1);

use PrettyPhp\Binary\RawSocket;
use PrettyPhp\Binary\Security\RateLimiter;
use PrettyPhp\Binary\Security\RateLimitException;
use PrettyPhp\Binary\Security\SecurityAudit;
use PrettyPhp\Binary\Security\SecurityConfig;
use PrettyPhp\Binary\Security\SecurityException;
use PrettyPhp\Binary\Socket;

mutates(RateLimiter::class, SecurityConfig::class, SecurityAudit::class);

/**
 * Manually advanced clock for deterministic rate limiter tests
 */
function fakeClock(float &$now): Closure
{
    return static function () use (&$now): float {
        return $now;
    };
}

describe('RateLimiter token bucket', function (): void {
    it('rejects non-positive limits', function (int $requests, int $window, string $message): void {
        expect(fn (): RateLimiter => new RateLimiter($requests, $window))->toThrow(SecurityException::class, $message);
    })->with([
        [0, 1, 'Max requests must be positive'],
        [1, 0, 'Window seconds must be positive'],
    ]);

    it('accepts the smallest positive limits', function (): void {
        expect(new RateLimiter(1, 1)->getRemainingTokens())->toBe(1);
    });

    it('consumes, waits for and refills tokens', function (): void {
        $now = 1000.0;
        $limiter = new RateLimiter(2, 10, fakeClock($now));

        expect($limiter->isEnabled())->toBeTrue();
        expect($limiter->getTimeUntilNextToken())->toBe(0.0);
        expect($limiter->tryOperation())->toBeTrue();
        expect($limiter->getRemainingTokens())->toBe(1);
        expect($limiter->tryOperation())->toBeTrue();
        expect($limiter->tryOperation())->toBeFalse();
        expect($limiter->getRemainingTokens())->toBe(0);
        expect($limiter->getTimeUntilNextToken())->toBe(5.0);

        $now += 4.9;
        expect($limiter->getRemainingTokens())->toBe(0);

        $now += 0.1;
        expect($limiter->getRemainingTokens())->toBe(1);

        $now += 100.0;
        expect($limiter->getRemainingTokens())->toBe(2);
    });

    it('keeps fractional refill progress between checks', function (): void {
        $now = 0.0;
        $limiter = new RateLimiter(10, 50, fakeClock($now)); // one token per 5 seconds
        for ($i = 0; $i < 10; $i++) {
            $limiter->checkLimit();
        }

        $now = 7.5;
        expect($limiter->getRemainingTokens())->toBe(1);

        $now = 10.0;
        expect($limiter->getRemainingTokens())->toBe(2);
    });

    it('throws with operation details when exhausted', function (): void {
        $now = 0.0;
        $limiter = new RateLimiter(1, 60, fakeClock($now));
        $limiter->checkLimit('first');

        expect(fn () => $limiter->checkLimit('upload'))
            ->toThrow(RateLimitException::class, 'Rate limit exceeded for upload: Maximum 1 requests per 60 seconds');
    });

    it('does not consume tokens while disabled and refills on reset', function (): void {
        $now = 0.0;
        $limiter = new RateLimiter(1, 60, fakeClock($now));
        $limiter->disable();

        expect($limiter->isEnabled())->toBeFalse();
        expect($limiter->tryOperation())->toBeTrue();

        $limiter->checkLimit();
        expect($limiter->getRemainingTokens())->toBe(1);

        $limiter->enable();
        $limiter->checkLimit();

        expect($limiter->getRemainingTokens())->toBe(0);
        $limiter->reset();
        expect($limiter->getRemainingTokens())->toBe(1);
    });

    it('provides presets', function (): void {
        expect(RateLimiter::strict()->getRemainingTokens())->toBe(100);
        expect(RateLimiter::permissive()->getRemainingTokens())->toBe(10000);
        expect(RateLimiter::default()->getRemainingTokens())->toBe(1000);
    });

    it('computes the wait time from the refill rate', function (): void {
        $now = 0.0;
        $limiter = new RateLimiter(4, 2, fakeClock($now)); // two tokens per second
        for ($i = 0; $i < 4; $i++) {
            $limiter->checkLimit();
        }

        expect($limiter->getTimeUntilNextToken())->toBe(0.5);
    });
});

describe('SecurityConfig values', function (): void {
    afterEach(function (): void {
        SecurityConfig::reset();
    });

    it('has documented defaults', function (): void {
        expect(SecurityConfig::DEFAULT_MAX_BUFFER_SIZE)->toBe(10_485_760);
        expect(SecurityConfig::DEFAULT_MAX_NESTING_DEPTH)->toBe(100);
        expect(SecurityConfig::DEFAULT_RATE_LIMIT_REQUESTS)->toBe(1000);
        expect(SecurityConfig::DEFAULT_RATE_LIMIT_WINDOW)->toBe(60);
        expect(SecurityConfig::isStrictMode())->toBeFalse();
    });

    it('accepts the smallest positive limits and rejects zero', function (): void {
        SecurityConfig::setMaxBufferSize(1);
        SecurityConfig::setMaxNestingDepth(1);
        expect(SecurityConfig::getMaxBufferSize())->toBe(1);
        expect(SecurityConfig::getMaxNestingDepth())->toBe(1);
        expect(fn () => SecurityConfig::setMaxBufferSize(0))->toThrow(SecurityException::class, 'Max buffer size must be positive');
        expect(fn () => SecurityConfig::setMaxNestingDepth(0))->toThrow(SecurityException::class, 'Max nesting depth must be positive');
    });

    it('resets strict mode', function (): void {
        SecurityConfig::enableStrictMode();
        SecurityConfig::disableStrictMode();
        expect(SecurityConfig::isStrictMode())->toBeFalse();
        SecurityConfig::enableStrictMode();
        SecurityConfig::reset();
        expect(SecurityConfig::isStrictMode())->toBeFalse();
    });
});

describe('SecurityAudit findings', function (): void {
    afterEach(function (): void {
        SecurityConfig::reset();
    });

    it('audits sockets', function (): void {
        $plain = Socket::udp();
        expect(new SecurityAudit()->auditSocket($plain))->toBe([[
            'severity' => 'warning',
            'message' => 'Socket does not have rate limiting configured',
            'recommendation' => 'Consider using setRateLimiter() to prevent abuse',
        ]]);

        $plain->setRateLimiter(new RateLimiter(1, 1));
        expect(new SecurityAudit()->auditSocket($plain))->toBe([]);

        $raw = new RawSocket(AF_INET, SOCK_DGRAM, SOL_UDP);
        $raw->setRateLimiter(new RateLimiter(1, 1));

        expect(new SecurityAudit()->auditSocket($raw))->toBe([[
            'severity' => 'critical',
            'message' => 'Using raw socket which requires elevated privileges',
            'recommendation' => 'Ensure proper security measures: validate input, implement rate limiting, '
                . 'run with minimum required privileges, and audit all packet handling code',
        ]]);
    });

    it('flags limits only above the thresholds', function (): void {
        SecurityConfig::enableStrictMode();
        SecurityConfig::setMaxBufferSize(100 * 1024 * 1024);
        SecurityConfig::setMaxNestingDepth(1000);
        expect(SecurityAudit::validateConfiguration())->toBe([[
            'severity' => 'success',
            'message' => 'Security configuration is within recommended limits',
            'recommendation' => null,
        ]]);

        SecurityConfig::disableStrictMode();
        SecurityConfig::setMaxBufferSize(100 * 1024 * 1024 + 1);
        SecurityConfig::setMaxNestingDepth(1001);
        expect(SecurityAudit::validateConfiguration())->toBe([
            [
                'severity' => 'warning',
                'message' => 'Max buffer size is very large',
                'recommendation' => 'Current: 104857601 bytes. Consider reducing to prevent memory exhaustion attacks',
            ],
            [
                'severity' => 'warning',
                'message' => 'Max nesting depth is very high',
                'recommendation' => 'Current: 1001. Consider reducing to prevent stack overflow attacks',
            ],
            [
                'severity' => 'info',
                'message' => 'Strict mode is disabled',
                'recommendation' => 'Enable strict mode for production environments',
            ],
        ]);
    });

    it('renders the full report', function (): void {
        $report = SecurityAudit::generateReport();
        expect($report)->toMatch('/^# Security Audit Report\n\nGenerated: \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\n\n/');
        expect(preg_replace('/Generated: .*\n/', "Generated: <date>\n", $report))->toMatchSnapshot();

        SecurityConfig::enableStrictMode();
        expect(preg_replace('/Generated: .*\n/', "Generated: <date>\n", SecurityAudit::generateReport()))->toMatchSnapshot();
    });
});
