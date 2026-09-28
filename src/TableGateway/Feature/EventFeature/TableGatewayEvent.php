<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature\EventFeature;

use Laminas\EventManager\EventInterface;
use Override;
use PhpDb\TableGateway\AbstractTableGateway;

/**
 * @implements EventInterface<AbstractTableGateway|null, array<array-key, mixed>|object>
 */
final class TableGatewayEvent implements EventInterface
{
    protected ?AbstractTableGateway $target = null;

    protected ?string $name = null;

    /** @var array<array-key, mixed>|object */
    protected array|object $params = [];

    #[Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get a single parameter by name
     *
     * @param string|int $name
     * @param mixed $default Default value to return if parameter does not exist
     */
    #[Override]
    public function getParam($name, $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Get parameters passed to the event
     */
    #[Override]
    public function getParams(): array|object
    {
        return $this->params;
    }

    /**
     * Get target/context from which event was triggered
     */
    #[Override]
    public function getTarget(): ?AbstractTableGateway
    {
        return $this->target;
    }

    /**
     * Has this event indicated event propagation should stop?
     */
    #[Override]
    public function propagationIsStopped(): false
    {
        return false;
    }

    /**
     * Set the event name
     *
     * @param string $name
     */
    #[Override]
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Set a single parameter by key
     *
     * @param string|int $name
     * @param mixed $value
     *
     * @mago-expect analysis:possibly-invalid-array-access
     */
    #[Override]
    public function setParam($name, $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * Set event parameters
     *
     * @param array<array-key, mixed>|object $params
     * @phpstan-ignore selfOut.type
     *
     * @mago-expect analysis:unused-template-parameter
     */
    #[Override]
    public function setParams($params): void
    {
        $this->params = $params;
    }

    /**
     * Set the event target/context
     *
     * @param object|string|null $target
     * @phpstan-ignore selfOut.type
     *
     * @mago-expect analysis:unused-template-parameter
     * @mago-expect analysis:property-type-coercion
     */
    #[Override]
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * Indicate whether or not the parent EventManagerInterface should stop propagating events
     *
     * @param bool $flag
     */
    #[Override]
    public function stopPropagation($flag = true): void {}
}
