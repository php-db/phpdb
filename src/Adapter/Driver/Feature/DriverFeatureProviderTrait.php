<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Feature;

/**
 * Trait implementation of DriverFeatureProviderInterface.
 *
 * This trait can be used in any driver that needs to support features.
 * Primarily used in the Pdo driver, but can be adapted for others.
 */
trait DriverFeatureProviderTrait
{
    /**
     * Add feature
     *
     * todo: needs improvement
     *
     * @return $this Provides a fluent interface
     */
    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface
    {
        $feature->setDriver($this);
        $this->features[$feature::class] = $feature;
        return $this;
    }

    public function addFeatures(array $features): DriverFeatureProviderInterface
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    /**
     * Setup the default features for Pdo
     *
     * @deprecated since 3.0.0, use addFeatures() instead
     */
    public function setupDefaultFeatures(): DriverFeatureProviderInterface
    {
        $driverName = $this->connection->getDriverName();
        if ($driverName === 'sqlite') {
            $this->addFeature(null, new Feature\SqliteRowCounter());
            return $this;
        }

        if ($driverName === 'oci') {
            $this->addFeature(null, new Feature\OracleRowCounter());
            return $this;
        }

        return $this;
    }
}
