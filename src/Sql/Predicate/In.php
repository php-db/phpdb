<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\AbstractExpression;
use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\ExpressionData;
use Laminas\Db\Sql\Select;

use function vsprintf;

class In extends AbstractExpression implements PredicateInterface
{
    protected ?Argument $identifier = null;
    protected ?Argument $valueSet   = null;
    protected string $specification = '%s IN %s';

    /**
     * Constructor
     */
    public function __construct(
        null|float|int|string|array|Argument $identifier = null,
        null|array|Select|Argument $valueSet = null
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
    public function setIdentifier(
        null|string|int|float|array|Argument $value,
        ArgumentType $type = ArgumentType::Identifier
    ): static {
        $this->identifier = $value instanceof Argument ? $value : new Argument($value, $type);

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
    public function setValueSet(array|Select|Argument $valueSet): static
    {
        $this->valueSet = $valueSet instanceof Argument ? $valueSet : new Argument($valueSet);

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
     */
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        if ($this->identifier === null) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if ($this->valueSet === null) {
            throw new InvalidArgumentException('Value set must be provided for IN predicate');
        }

        $specification = vsprintf($this->specification, [
            $this->identifier->getSpecification(),
            $this->valueSet->getSpecification(),
        ]);

        return new ExpressionData(
            $specification,
            [
                $this->identifier,
                $this->valueSet,
            ]
        );
    }
}
