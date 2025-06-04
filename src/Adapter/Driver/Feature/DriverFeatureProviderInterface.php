<?php

namespace Laminas\Db\Adapter\Driver\Feature;

interface DriverFeatureProviderInterface
{
    public const DEFAULT_FEATURES = [];

    /**
     * Setup the default features for Pdo
     * @deprecated since 3.0.0, use addFeature() instead
     */
    public function setupDefaultFeatures(): DriverFeatureProviderInterface;

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
