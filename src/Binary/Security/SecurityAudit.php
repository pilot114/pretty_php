<?php

declare(strict_types=1);

namespace PrettyPhp\Binary\Security;

use PrettyPhp\Binary\Binary;
use PrettyPhp\Binary\Socket;
use PrettyPhp\Binary\RawSocket;
use ReflectionClass;

/**
 * SecurityAudit - Audits binary operations for security issues
 *
 * Provides static analysis and runtime checks for security vulnerabilities
 * in binary operations and socket usage.
 */
class SecurityAudit
{
    /**
     * @var array<int, array{severity: string, message: string, recommendation: string}>
     */
    private array $findings = [];

    /**
     * Audit a binary structure class
     *
     * @param class-string $className
     * @return array<int, array{severity: string, message: string, recommendation: string}>
     */
    public function auditBinaryStructure(string $className): array
    {
        $this->findings = [];
        $reflectionClass = new ReflectionClass($className);

        // Check for variable-length fields without size constraints
        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Binary::class);
            if ($attributes === []) {
                continue;
            }

            $binaryAttr = $attributes[0]->newInstance();
            $format = $binaryAttr->format;

            // Check for unbounded string formats
            if ($format === 'A*') {
                $this->addFinding(
                    'warning',
                    sprintf("Property '%s' uses unbounded string format (A*)", $property->getName()),
                    'Consider using a fixed-length format like A20 to prevent buffer issues'
                );
            }

            // Check for nested structures (potential deep recursion)
            if (class_exists($format)) {
                $this->addFinding(
                    'info',
                    sprintf("Property '%s' contains nested structure: %s", $property->getName(), $format),
                    'Ensure nesting depth limits are configured to prevent stack overflow'
                );
            }
        }

        return $this->findings;
    }

    /**
     * Check if a socket has rate limiting enabled
     *
     * @return array<int, array{severity: string, message: string, recommendation: string}>
     */
    public function auditSocket(Socket $socket): array
    {
        $this->findings = [];

        if (!$socket->getRateLimiter() instanceof \PrettyPhp\Binary\Security\RateLimiter) {
            $this->addFinding(
                'warning',
                'Socket does not have rate limiting configured',
                'Consider using setRateLimiter() to prevent abuse'
            );
        } else {
            $rateLimiter = $socket->getRateLimiter();
            if (!$rateLimiter->isEnabled()) {
                $this->addFinding(
                    'info',
                    'Socket has rate limiter but it is disabled',
                    'Enable rate limiting for production use'
                );
            }
        }

        // Check if it's a raw socket
        if ($socket instanceof RawSocket) {
            $this->addFinding(
                'critical',
                'Using raw socket which requires elevated privileges',
                'Ensure proper security measures: validate input, implement rate limiting, ' .
                'run with minimum required privileges, and audit all packet handling code'
            );
        }

        return $this->findings;
    }

    /**
     * Validate security configuration
     *
     * @return array<int, array{severity: string, message: string, recommendation: string|null}>
     */
    public static function validateConfiguration(): array
    {
        /** @var array<int, array{severity: string, message: string, recommendation: string|null}> $findings */
        $findings = [];

        $maxBufferSize = SecurityConfig::getMaxBufferSize();
        if ($maxBufferSize > 100 * 1024 * 1024) { // 100MB
            $findings[] = [
                'severity' => 'warning',
                'message' => 'Max buffer size is very large',
                'recommendation' => sprintf(
                    'Current: %d bytes. Consider reducing to prevent memory exhaustion attacks',
                    $maxBufferSize
                ),
            ];
        }

        $maxNestingDepth = SecurityConfig::getMaxNestingDepth();
        if ($maxNestingDepth > 1000) {
            $findings[] = [
                'severity' => 'warning',
                'message' => 'Max nesting depth is very high',
                'recommendation' => sprintf(
                    'Current: %d. Consider reducing to prevent stack overflow attacks',
                    $maxNestingDepth
                ),
            ];
        }

        if (!SecurityConfig::isStrictMode()) {
            $findings[] = [
                'severity' => 'info',
                'message' => 'Strict mode is disabled',
                'recommendation' => 'Enable strict mode for production environments',
            ];
        }

        if ($findings === []) {
            $findings[] = [
                'severity' => 'success',
                'message' => 'Security configuration is within recommended limits',
                'recommendation' => null,
            ];
        }

        return $findings;
    }

    /**
     * Generate a security report
     */
    public static function generateReport(): string
    {
        $report = "# Security Audit Report\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        $report .= "## Configuration\n\n";
        $findings = self::validateConfiguration();

        foreach ($findings as $finding) {
            $severity = $finding['severity'];
            $message = $finding['message'];
            $recommendation = $finding['recommendation'];

            $emoji = match ($severity) {
                'critical' => '🔴',
                'warning' => '⚠️',
                'info' => 'ℹ️',
                'success' => '✅',
                default => '•',
            };

            $report .= "{$emoji} **{$message}**\n";
            if ($recommendation !== null) {
                $report .= sprintf('   %s%s', $recommendation, PHP_EOL);
            }

            $report .= "\n";
        }

        $report .= "## Recommendations\n\n";
        $report .= "1. Always use rate limiting for network operations\n";
        $report .= "2. Set appropriate buffer size limits based on your use case\n";
        $report .= "3. Validate all input data before unpacking\n";
        $report .= "4. Use strict mode in production environments\n";
        $report .= "5. Regularly audit code using SecurityAudit::auditBinaryStructure()\n";
        $report .= "6. Implement proper error handling for security exceptions\n";

        return $report . "7. Run raw socket operations with minimum required privileges\n";
    }

    /**
     * Add a finding to the audit results
     */
    private function addFinding(string $severity, string $message, string $recommendation): void
    {
        $this->findings[] = [
            'severity' => $severity,
            'message' => $message,
            'recommendation' => $recommendation,
        ];
    }
}
