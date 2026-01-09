<?php

namespace PhpDb\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;

use function method_exists;

class FeatureSet
{
    public const APPLY_HALT = 'halt';

    protected AbstractRowGateway $rowGateway;

    /** @var AbstractFeature[] */
    protected array $features = [];

    protected array $magicSpecifications = [];

    public function __construct(array $features = [])
    {
        if ($features !== []) {
            $this->addFeatures($features);
        }
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setRowGateway(AbstractRowGateway $rowGateway): static
    {
        $this->rowGateway = $rowGateway;
        foreach ($this->features as $feature) {
            $feature->setRowGateway($this->rowGateway);
        }
        return $this;
    }

    /**
     * @return AbstractFeature
     */
    public function getFeatureByClassName(string $featureClassName): AbstractFeature|false
    {
        $feature = false;
        foreach ($this->features as $potentialFeature) {
            if ($potentialFeature instanceof $featureClassName) {
                $feature = $potentialFeature;
                break;
            }
        }
        return $feature;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addFeatures(array $features): static
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addFeature(AbstractFeature $feature): static
    {
        $this->features[] = $feature;
        $feature->setRowGateway($this->rowGateway);
        return $this;
    }

    public function apply(string $method, array $args): void
    {
        foreach ($this->features as $feature) {
            if (method_exists($feature, $method)) {
                $return = $feature->$method(...$args);
                if ($return === self::APPLY_HALT) {
                    break;
                }
            }
        }
    }

    /**
     * @param string $property
     */
    public function canCallMagicGet($property): bool
    {
        return false;
    }

    /**
     * @param string $property
     */
    public function callMagicGet($property): mixed
    {
        return null;
    }

    public function canCallMagicSet(string $property): bool
    {
        return false;
    }

    public function callMagicSet(string $property, mixed $value): mixed
    {
        return null;
    }

    public function canCallMagicCall(string $method): bool
    {
        return false;
    }

    public function callMagicCall(string $method, array $arguments): mixed
    {
        return null;
    }
}
