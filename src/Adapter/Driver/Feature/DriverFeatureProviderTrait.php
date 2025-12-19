<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Feature;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\RuntimeException;

use function sprintf;

/**
 * Trait implementation of DriverFeatureProviderInterface.
 *
 * This trait can be used in any driver that needs to support features.
 * Primarily used in the Pdo driver, but can be adapted for others.
 *
 * @phpstan-ignore trait.unused
 */
trait DriverFeatureProviderTrait
{
    /** @var array<class-string, DriverFeatureInterface> */
    protected array $features = [];

    #[Override]
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

    #[Override]
    public function addFeatures(array $features): DriverFeatureProviderInterface
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    #[Override]
    public function getFeature(string $name): DriverFeatureInterface|false
    {
        return $this->features[$name] ?? false;
    }
}
