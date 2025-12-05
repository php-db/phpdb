<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\IncreaseDeclareStrictTypesRector;

return RectorConfig::configure()
                   ->withPaths([
                       __DIR__ . '/src',
                       __DIR__ . '/test',
                   ])
                   ->withRules([
                       IncreaseDeclareStrictTypesRector::class,
                       AddTypeToConstRector::class,
                       AddOverrideAttributeToOverriddenMethodsRector::class,
                   ])
                   ->withPreparedSets(
                       codeQuality: true
                   );
