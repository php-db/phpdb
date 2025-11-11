<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Feature;

use PhpDb\Adapter\Driver\DriverInterface;

/**
 * @property array<class-string, DriverInterface> $features
 */
interface DriverFeatureProviderInterface
{
    /** @param DriverFeatureInterface[] $features */
    public function addFeatures(array $features): DriverFeatureProviderInterface;

    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface;

    /** Get feature by class FQCN. */
    public function getFeature(string $name): DriverFeatureInterface|false;
}
