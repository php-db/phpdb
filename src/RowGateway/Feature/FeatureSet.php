<?php

declare(strict_types=1);

namespace PhpDb\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;

use function method_exists;

/**
 * @final
 */
class FeatureSet
{
    final public const string APPLY_HALT = 'halt';

    protected ?AbstractRowGateway $rowGateway = null;

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
        $this->features[] = $feature;
        if (null !== $this->rowGateway) {
            $feature->setRowGateway($this->rowGateway);
        }
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
     * @mago-expect analysis:unused-parameter
     *
     * @param array<array-key, mixed> $arguments
     */
    public function callMagicCall(string $method, array $arguments): mixed
    {
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
     * @mago-expect analysis:unused-parameter
     */
    public function canCallMagicCall(string $method): bool
    {
        return false;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function canCallMagicGet(string $property): false
    {
        return false;
    }

    /**
     * @mago-expect analysis:unused-parameter
     */
    public function canCallMagicSet(string $property): false
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

    public function setRowGateway(AbstractRowGateway $rowGateway): static
    {
        $this->rowGateway = $rowGateway;
        foreach ($this->features as $feature) {
            $feature->setRowGateway($this->rowGateway);
        }
        return $this;
    }
}
