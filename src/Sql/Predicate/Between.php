<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;

class Between extends AbstractExpression implements PredicateInterface
{
    protected string $specification = '%1$s BETWEEN %2$s AND %3$s';

    protected ?ArgumentInterface $identifier = null;

    protected ?ArgumentInterface $minValue = null;

    protected ?ArgumentInterface $maxValue = null;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|int|float|string|ArgumentInterface $minValue = null,
        null|int|float|string|ArgumentInterface $maxValue = null
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
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : Argument::identifier($identifier);

        return $this;
    }

    /**
     * Get identifier for comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set minimum value for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setMinValue(null|int|float|string|bool|ArgumentInterface $minValue): static
    {
        $this->minValue = $minValue instanceof ArgumentInterface
            ? $minValue
            : Argument::value($minValue);

        return $this;
    }

    /**
     * Get minimum value for comparison
     */
    public function getMinValue(): ?ArgumentInterface
    {
        return $this->minValue;
    }

    /**
     * Set maximum value for comparison
     *
     * @return $this Provides a fluent interface
     */
    public function setMaxValue(null|int|float|string|bool|ArgumentInterface $maxValue): static
    {
        $this->maxValue = $maxValue instanceof ArgumentInterface
            ? $maxValue
            : Argument::value($maxValue);

        return $this;
    }

    /**
     * Get maximum value for comparison
     */
    public function getMaxValue(): ?ArgumentInterface
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
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->minValue instanceof ArgumentInterface) {
            throw new InvalidArgumentException('minValue must be specified');
        }

        if (! $this->maxValue instanceof ArgumentInterface) {
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
