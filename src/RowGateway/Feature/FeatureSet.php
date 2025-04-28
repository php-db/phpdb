<?php

namespace Laminas\Db\RowGateway\Feature;

use Laminas\Db\RowGateway\AbstractRowGateway;

use function call_user_func_array;
use function method_exists;

final class FeatureSet
{
    public const APPLY_HALT = 'halt';

    /** @var AbstractRowGateway */
    protected $rowGateway;

    /** @var AbstractFeature[] */
    protected $features = [];

    /** @var array */
    protected $magicSpecifications = [];

    public function __construct(array $features = [])
    {
        if ($features) {
            $this->addFeatures($features);
        }
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setRowGateway(AbstractRowGateway $rowGateway)
    {
        $this->rowGateway = $rowGateway;
        foreach ($this->features as $feature) {
            $feature->setRowGateway($this->rowGateway);
        }
        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addFeatures(array $features)
    {
        foreach ($features as $feature) {
            $this->addFeature($feature);
        }
        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addFeature(AbstractFeature $feature)
    {
        $this->features[] = $feature;
        $feature->setRowGateway($feature);
        return $this;
    }

    /**
     * @param string $method
     * @param array $args
     * @return void
     */
    public function apply($method, $args)
    {
        foreach ($this->features as $feature) {
            if (method_exists($feature, $method)) {
                $return = call_user_func_array([$feature, $method], $args);
                if ($return === self::APPLY_HALT) {
                    break;
                }
            }
        }
    }
}
