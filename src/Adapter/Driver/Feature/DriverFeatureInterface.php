<?php

namespace Laminas\Db\Adapter\Driver\Feature;

interface DriverFeatureInterface
{
    /**
     * Setup the default features for Pdo
     */
    public function setupDefaultFeatures(): DriverFeatureInterface;

    /**
     * Add feature
     *
     * todo: narrow down the type of $feature
     */
    public function addFeature(string $name, mixed $feature): DriverFeatureInterface;

    /**
     * Get feature
     *
     * todo: narrow return type if possible
     */
    public function getFeature(string $name): mixed;
}
