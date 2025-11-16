<?php

// rector.php
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/test'])
    ->withTypeCoverageLevel(PHP_INT_MAX)      // Apply ALL type coverage rules
    ->withDeadCodeLevel(PHP_INT_MAX)          // Apply ALL dead code rules
    ->withCodeQualityLevel(PHP_INT_MAX)       // Apply ALL code quality rules
    ->withCodingStyleLevel(PHP_INT_MAX);