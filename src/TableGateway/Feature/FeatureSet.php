<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\TableGatewayInterface;

use function method_exists;

/**
 * @api
 */
class FeatureSet
{
    public const string APPLY_HALT = 'halt';

    protected ?AbstractTableGateway $tableGateway = null;

    /** @var FeatureInterface[] */
    protected array $features = [];

    /** @var array<array-key, mixed> */
    protected array $magicSpecifications = [];

    /** @param FeatureInterface[] $features */
    public function __construct(array $features = [])
    {
        if ([] !== $features) {
            $this->addFeatures($features);
        }
    }

    public function addFeature(FeatureInterface $feature): static
    {
        if ($this->tableGateway instanceof TableGatewayInterface) {
            $feature->setTableGateway($this->tableGateway);
        }
        $this->features[] = $feature;
        return $this;
    }

    /** @param FeatureInterface[] $features */
    public function addFeatures(array $features): static
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    /**
     * @param array<array-key, mixed> $args
     *
     * @mago-expect analysis:string-member-selector
     * @mago-expect analysis:mixed-assignment
     */
    public function apply(string $method, array $args): void
    {
        foreach ($this->features as $feature) {
            if (! method_exists($feature, $method)) {
                continue;
            }

            $return = $feature->$method(...$args);
            if (self::APPLY_HALT === $return) {
                break;
            }
        }
    }

    /**
     * Call method of on added feature as though it were a local method
     *
     * @param array<array-key, mixed> $arguments
     *
     * @mago-expect analysis:string-member-selector
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

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function callMagicGet(string $property): mixed
    {
        return null;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function callMagicSet(string $property, mixed $value): mixed
    {
        return null;
    }

    /**
     * Is the method requested available in one of the added features
     */
    public function canCallMagicCall(string $method): bool
    {
        if ([] !== $this->features) {
            foreach ($this->features as $feature) {
                if (method_exists($feature, $method)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function canCallMagicGet(string $property): bool
    {
        return false;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function canCallMagicSet(string $property): bool
    {
        return false;
    }

    public function getFeatureByClassName(string $featureClassName): ?FeatureInterface
    {
        $feature = null;
        foreach ($this->features as $potentialFeature) {
            if (! $potentialFeature instanceof $featureClassName) {
                continue;
            }

            $feature = $potentialFeature;
            break;
        }
        return $feature;
    }

    public function setTableGateway(AbstractTableGateway $tableGateway): static
    {
        $this->tableGateway = $tableGateway;
        foreach ($this->features as $feature) {
            $feature->setTableGateway($this->tableGateway);
        }
        return $this;
    }
}
