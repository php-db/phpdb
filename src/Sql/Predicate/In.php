<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\AbstractExpression;
use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Exception\InvalidArgumentException;

use function vsprintf;

class In extends AbstractExpression implements PredicateInterface
{
    protected ?Argument $identifier = null;

    protected ?Argument $valueSet = null;

    /** @var string */
    protected string $specification = '%s IN %s';

    /**
     * Constructor
     */
    public function __construct(
        null|float|int|string|array|Argument $identifier = null,
        null|array|Argument $valueSet = null
    ) {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }
        if ($valueSet !== null) {
            $this->setValueSet($valueSet);
        }
    }

    /**
     * Set identifier for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setIdentifier(null|string|int|float|array|Argument $value, ArgumentType $type = ArgumentType::Value): static
    {
        $this->identifier = ($value instanceof Argument) ? $value : new Argument($value, $type);

        return $this;
    }

    /**
     * Get identifier of comparison
     */
    public function getIdentifier(): ?Argument
    {
        return $this->identifier;
    }

    /**
     * Set set of values for IN comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setValueSet(array|Argument $valueSet = null): static
    {
        $this->valueSet = ($valueSet instanceof Argument) ? $valueSet : new Argument($valueSet);

        return $this;
    }

    /**
     * Gets set of values in IN comparison
     */
    public function getValueSet(): ?Argument
    {
        return $this->valueSet;
    }

    /**
     * Return array of parts for where statement
     *
     * @return array
     */
    #[\Override]
    public function getExpressionData(): array
    {
        $identifier = $this->getIdentifier();
        $values = $this->getValueSet();
        if ($values === null) {
            throw new InvalidArgumentException('Value set must be provided for IN predicate');
        }

        $specification = vsprintf(
            $this->specification,
            ['%s', '%s']
        );
        $replacements  = [$identifier, $values];

        return [
            [
                $specification,
                $replacements,
            ],
        ];
    }
}
