<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Feature;

use Laminas\Db\Adapter\Driver\DriverInterface;

/**
 *
 * @property array<class-string, DriverInterface> $features
 */
interface DriverFeatureProviderInterface
{
    /**
     * Add features
     *
     * @param DriverFeatureInterface[] $features
     */
    public function addFeatures(array $features): DriverFeatureProviderInterface;

    /**
     * Add feature
     */
    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface;

    /**
     * Get feature
     *
     * todo: narrow to DriverFeatureInterface|false once PHP 8.2 is the minimum version
     */
    public function getFeature(string $name): DriverFeatureInterface|bool;
}
