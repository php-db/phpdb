<?php

namespace PhpDb\TableGateway\Feature\EventFeature;

use ArrayAccess;
use PhpDb\TableGateway\AbstractTableGateway;
use Laminas\EventManager\EventInterface;
use Override;

class TableGatewayEvent implements EventInterface
{
    /** @var AbstractTableGateway */
    protected $target;

    /** @var null */
    protected $name;

    /** @var array|ArrayAccess */
    protected $params = [];

    /**
     * Get event name
     *
     * @return string|null
     */
    #[Override] public function getName()
    {
        return $this->name;
    }

    /**
     * Get target/context from which event was triggered
     *
     * @return AbstractTableGateway
     */
    #[Override] public function getTarget()
    {
        return $this->target;
    }

    /**
     * Get parameters passed to the event
     *
     * @return array|ArrayAccess
     */
    #[Override] public function getParams()
    {
        return $this->params;
    }

    /**
     * Get a single parameter by name
     *
     * @param  string $name
     * @param  mixed $default Default value to return if parameter does not exist
     * @return mixed
     */
    #[Override] public function getParam($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set the event name
     *
     * @param  string $name
     * @return void
     */
    #[Override] public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the event target/context
     *
     * @param  null|string|object $target
     * @return void
     */
    #[Override] public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * Set event parameters
     *
     * @param  string $params
     * @return void
     */
    #[Override] public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Set a single parameter by key
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    #[Override] public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Indicate whether or not the parent EventManagerInterface should stop propagating events
     *
     * @param  bool $flag
     * @return void
     */
    #[Override] public function stopPropagation($flag = true)
    {
    }

    /**
     * Has this event indicated event propagation should stop?
     *
     * @return bool
     */
    #[Override] public function propagationIsStopped()
    {
        return false;
    }
}
