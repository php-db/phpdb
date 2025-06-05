<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Feature;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Exception\RuntimeException;

/**
 * Trait implementation of DriverFeatureProviderInterface.
 *
 * This trait can be used in any driver that needs to support features.
 * Primarily used in the Pdo driver, but can be adapted for others.
 */
trait DriverFeatureProviderTrait
{
    /**
     * @var array<class-string, DriverFeatureInterface>
     */
    protected array $features = [];

    /**
     * Add feature
     */
    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface
    {
        if (! $this instanceof DriverInterface) {
            throw new RuntimeException(sprintf(
                '%s can only be composed into %s',
                __TRAIT__,
                DriverInterface::class
            ));
        }

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
     * Get feature
     */
    public function getFeature(string $name): DriverFeatureInterface|bool
    {
        return $this->features[$name] ?? false;
    }

    /**
     * Setup the default features for Pdo
     *
     * @deprecated since 3.0.0, use addFeatures() instead
     */
    public function setupDefaultFeatures(): DriverFeatureProviderInterface
    {
        // $driverName = $this->connection->getDriverName();
        // if ($driverName === 'sqlite') {
        //     $this->addFeature(null, new Feature\SqliteRowCounter());
        //     return $this;
        // }

        // if ($driverName === 'oci') {
        //     $this->addFeature(null, new Feature\OracleRowCounter());
        //     return $this;
        // }

        return $this;
    }
}
