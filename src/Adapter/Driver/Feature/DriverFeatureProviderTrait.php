<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Feature;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\RuntimeException;

/**
 * Trait implementation of DriverFeatureProviderInterface.
 *
 * This trait can be used in any driver that needs to support features.
 * Primarily used in the Pdo driver, but can be adapted for others.
 *
 * @api
 *
 * @phpstan-ignore trait.unused
 */
trait DriverFeatureProviderTrait
{
    /** @var array<class-string, DriverFeatureInterface> */
    protected array $features = [];

    /** @throws RuntimeException When the composing class is not a feature-providing driver. */
    public function addFeature(DriverFeatureInterface $feature): DriverFeatureProviderInterface
    {
        $provider = $this->asFeatureProvidingDriver();

        $feature->setDriver($provider);
        $this->features[$feature::class] = $feature;
        return $provider;
    }

    /**
     * @param array<array-key, DriverFeatureInterface> $features
     *
     * @throws RuntimeException When the composing class is not a feature-providing driver.
     */
    public function addFeatures(array $features): DriverFeatureProviderInterface
    {
        $provider = $this->asFeatureProvidingDriver();

        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $provider;
    }

    public function getFeature(string $name): DriverFeatureInterface|false
    {
        return $this->features[$name] ?? false;
    }

    /** @throws RuntimeException When the composing class is not a feature-providing driver. */
    private function asFeatureProvidingDriver(): DriverInterface&DriverFeatureProviderInterface
    {
        if (! $this instanceof DriverInterface) {
            throw RuntimeException::forUncomposableTrait(__TRAIT__, DriverInterface::class);
        }

        if (! $this instanceof DriverFeatureProviderInterface) {
            throw RuntimeException::forUncomposableTrait(__TRAIT__, DriverFeatureProviderInterface::class);
        }

        return $this;
    }
}
