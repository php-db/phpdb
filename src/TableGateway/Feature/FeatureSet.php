<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\TableGatewayInterface;

use function method_exists;

class FeatureSet
{
    public const APPLY_HALT = 'halt';

    protected ?AbstractTableGateway $tableGateway = null;

    /** @var FeatureInterface[] */
    protected array $features = [];

    protected array $magicSpecifications = [];

    public function __construct(array $features = [])
    {
        if ($features !== []) {
            $this->addFeatures($features);
        }
    }

    public function setTableGateway(AbstractTableGateway $tableGateway): static
    {
        $this->tableGateway = $tableGateway;
        foreach ($this->features as $feature) {
            $feature->setTableGateway($this->tableGateway);
        }
        return $this;
    }

    public function getFeatureByClassName(string $featureClassName): ?FeatureInterface
    {
        $feature = null;
        foreach ($this->features as $potentialFeature) {
            if ($potentialFeature instanceof $featureClassName) {
                $feature = $potentialFeature;
                break;
            }
        }
        return $feature;
    }

    public function addFeatures(array $features): static
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    public function addFeature(FeatureInterface $feature): static
    {
        if ($this->tableGateway instanceof TableGatewayInterface) {
            $feature->setTableGateway($this->tableGateway);
        }
        $this->features[] = $feature;
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

    public function canCallMagicGet(string $property): bool
    {
        return false;
    }

    public function callMagicGet(string $property): mixed
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

    /**
     * Is the method requested available in one of the added features
     */
    public function canCallMagicCall(string $method): bool
    {
        if ($this->features !== []) {
            foreach ($this->features as $feature) {
                if (method_exists($feature, $method)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Call method of on added feature as though it were a local method
     */
    public function callMagicCall(string $method, array $arguments): mixed
    {
        foreach ($this->features as $feature) {
            if (method_exists($feature, $method)) {
                return $feature->$method($arguments);
            }
        }

        return null;
    }
}
