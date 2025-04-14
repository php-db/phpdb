<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use CustomRule\PHPUnit\ReplaceGetMockForAbstractClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/test',
    ])
    ->withRules([
        ReplaceGetMockForAbstractClassRector::class
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
