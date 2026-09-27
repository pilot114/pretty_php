<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        // PHP version sets - use the latest
        LevelSetList::UP_TO_PHP_85,

        // Code quality sets
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,

        // PHPUnit improvements
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withSkip([
        // Skip RemoveNonExistingVarAnnotationRector to preserve PHPStan type hints
        \Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        // Buggy with promoted properties: removes constructors that declare public readonly props
        \Rector\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector::class,
        // Rector does not know about #[\NoDiscard]: it treats (void) casts in benchmarks as dead code
        \Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector::class => [__DIR__ . '/tests/benchmarks'],
    ])
    ->withComposerBased(phpunit: true)
    ->withPhpSets(
        php85: true
    );
