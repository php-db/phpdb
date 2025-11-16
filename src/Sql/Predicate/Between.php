<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;

class Between extends AbstractExpression implements PredicateInterface
{
    protected string $specification = '%1$s BETWEEN %2$s AND %3$s';

    protected ?Argument $identifier = null;

    protected ?Argument $minValue = null;

    protected ?Argument $maxValue = null;

    /**
     * Constructor
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
    public function setIdentifier(
        null|string|int|float|array|Argument $value,
        ArgumentType $type = ArgumentType::Identifier
    ): static {
        $this->identifier = $value instanceof Argument ? $value : new Argument($value, $type);

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
    public function setMinValue(
        null|string|int|float|array|Argument $value,
        ArgumentType $type = ArgumentType::Value
    ): static {
        $this->minValue = $value instanceof Argument ? $value : new Argument($value, $type);

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
    public function setMaxValue(
        null|string|int|float|array|Argument $value,
        ArgumentType $type = ArgumentType::Value
    ): static {
        $this->maxValue = $value instanceof Argument ? $value : new Argument($value, $type);

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
     */
    public function getSpecification(): string
    {
        return $this->specification;
    }

    /**
     * Return "where" parts
     */
    #[Override]
    public function getExpressionData(): ExpressionData
    {
        if (! $this->identifier instanceof Argument) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->minValue instanceof Argument) {
            throw new InvalidArgumentException('minValue must be specified');
        }

        if (! $this->maxValue instanceof Argument) {
            throw new InvalidArgumentException('maxValue must be specified');
        }

        return new ExpressionData(
            $this->getSpecification(),
            [
                $this->identifier,
                $this->minValue,
                $this->maxValue,
            ]
        );
    }
}
