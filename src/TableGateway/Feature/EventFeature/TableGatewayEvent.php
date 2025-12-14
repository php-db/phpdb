<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature\EventFeature;

use Laminas\EventManager\EventInterface;
use PhpDb\TableGateway\AbstractTableGateway;

class TableGatewayEvent implements EventInterface
{
    /** @var AbstractTableGateway */
    protected $target;

    protected ?string $name = null;

    /** @var array|object */
    protected $params = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get target/context from which event was triggered
     *
     * @return AbstractTableGateway
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Get parameters passed to the event
     */
    public function getParams(): array|object
    {
        return $this->params;
    }

    /**
     * Get a single parameter by name
     *
     * @param string $name
     * @param mixed $default Default value to return if parameter does not exist
     */
    public function getParam($name, $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set the event name
     *
     * @param string|null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Set the event target/context
     *
     * @param null|string|object $target
     * @phpstan-ignore selfOut.type
     */
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * @param array|object $params
     * @phpstan-ignore selfOut.type
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }

    /**
     * Set a single parameter by key
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParam($name, $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * Indicate whether or not the parent EventManagerInterface should stop propagating events
     *
     * @param bool $flag
     */
    public function stopPropagation($flag = true): void
    {
    }

    /**
     * Has this event indicated event propagation should stop?
     */
    public function propagationIsStopped(): false
    {
        return false;
    }
}
