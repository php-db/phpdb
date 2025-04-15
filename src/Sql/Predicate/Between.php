<?php

namespace Laminas\Db\Sql\Predicate;

use Laminas\Db\Sql\AbstractExpression;
use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;

class Between extends AbstractExpression implements PredicateInterface
{
    protected string $specification = '%1$s BETWEEN %2$s AND %3$s';

    protected ?Argument $identifier = null;

    protected ?Argument $minValue = null;

    protected ?Argument $maxValue = null;

    /**
     * Constructor
     *
     * @param null|float|int|string|array|Argument  $identifier
     * @param  null|float|int|string|array|Argument $minValue
     * @param  null|float|int|string|array|Argument $maxValue
     */
    public function __construct(
        null|float|int|string|array|Argument $identifier = null,
        null|float|int|string|array|Argument $minValue = null,
        null|float|int|string|array|Argument $maxValue = null
    ) {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }
        if ($minValue !== null) {
            $this->setMinValue($minValue);
        }
        if ($maxValue !== null) {
            $this->setMaxValue($maxValue);
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
     * Get argument of comparison
     */
    public function getIdentifier(): ?Argument
    {
        return $this->identifier;
    }

    /**
     * Set minimum value or column for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setMinValue(null|string|int|float|array|Argument $value, ArgumentType $type = ArgumentType::Value): static
    {
        $this->minValue = ($value instanceof Argument) ? $value : new Argument($value, $type);
        return $this;
    }

    /**
     * Get minimum value or column for comparison
     */
    public function getMinValue(): ?Argument
    {
        return $this->minValue;
    }

    /**
     * Set maximum boundary for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setMaxValue(null|string|int|float|array|Argument $value, ArgumentType $type = ArgumentType::Value): static
    {
        $this->maxValue = ($value instanceof Argument) ? $value : new Argument($value, $type);
        return $this;
    }

    /**
     * Get maximum value or column for comparison
     */
    public function getMaxValue(): ?Argument
    {
        return $this->maxValue;
    }

    /**
     * Set specification string to use in forming SQL predicate
     *
     * @return $this Provides a fluent interface
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;
        return $this;
    }

    /**
     * Get specification string to use in forming SQL predicate
     *
     * @return string
     */
    public function getSpecification(): string
    {
        return $this->specification;
    }

    /**
     * Return "where" parts
     *
     * @return array
     */
    #[\Override]
    public function getExpressionData(): array
    {
        return [
            [
                $this->getSpecification(),
                [$this->identifier, $this->minValue, $this->maxValue]
            ],
        ];
    }
}
