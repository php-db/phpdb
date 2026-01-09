<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature\EventFeature;

use Laminas\EventManager\EventInterface;
use PhpDb\TableGateway\AbstractTableGateway;

class TableGatewayEvent implements EventInterface
{
    protected ?AbstractTableGateway $target = null;

    protected ?string $name = null;

    protected array|object $params = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get target/context from which event was triggered
     */
    public function getTarget(): ?AbstractTableGateway
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
     * @param mixed $default Default value to return if parameter does not exist
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Set the event name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the event target/context
     *
     * @phpstan-ignore selfOut.type
     */
    public function setTarget(null|string|object $target): void
    {
        $this->target = $target;
    }

    /**
     * @phpstan-ignore selfOut.type
     */
    public function setParams(array|object $params): void
    {
        $this->params = $params;
    }

    /**
     * Set a single parameter by key
     */
    public function setParam(string $name, mixed $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * Indicate whether or not the parent EventManagerInterface should stop propagating events
     */
    public function stopPropagation(bool $flag = true): void
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
