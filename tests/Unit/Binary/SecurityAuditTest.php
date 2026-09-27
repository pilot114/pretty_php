<?php

declare(strict_types=1);

use PrettyPhp\Binary\Security\SecurityAudit;
use PrettyPhp\Binary\Security\SecurityConfig;

mutates(\PrettyPhp\Binary\Security\SecurityAudit::class, \PrettyPhp\Binary\Security\SecurityConfig::class);

describe('SecurityAudit findings', function (): void {
    afterEach(function (): void {
        \PrettyPhp\Binary\Security\SecurityConfig::reset();
    });

    it('audits structures with unbounded and nested fields', function (): void {
        $messages = array_column(
            new SecurityAudit()->auditBinaryStructure(\Tests\Support\TestDocumentedPacket::class),
            'message'
        );
        expect($messages)->toContain("Property 'inner' contains nested structure: Tests\\Support\\TestInnerPacket");

        $unbounded = array_column(
            new SecurityAudit()->auditBinaryStructure(\PrettyPhp\Binary\TCPPacket::class),
            'message'
        );
        expect($unbounded)->toContain("Property 'data' uses unbounded string format (A*)");
    });

    it('reports risky and safe configurations', function (): void {
        SecurityConfig::setMaxBufferSize(200 * 1024 * 1024);
        SecurityConfig::setMaxNestingDepth(5000);
        $messages = array_column(SecurityAudit::validateConfiguration(), 'message');
        expect($messages)->toContain('Max buffer size is very large')->toContain('Max nesting depth is very high');
        expect(SecurityAudit::generateReport())->toContain('⚠️');

        SecurityConfig::reset();
        SecurityConfig::enableStrictMode();
        expect(SecurityAudit::validateConfiguration()[0]['severity'])->toBe('success');
        expect(SecurityAudit::generateReport())->toContain('✅');
    });
});
