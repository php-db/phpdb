<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use LogicException;
use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;

class Between extends AbstractExpression implements PredicateInterface
{
    protected string $operator = 'BETWEEN';

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
     */
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : new Identifier($identifier);

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
     */
    public function setMinValue(null|int|float|string|bool|ArgumentInterface $minValue): static
    {
        $this->minValue = $minValue instanceof ArgumentInterface
            ? $minValue
            : new Value($minValue);

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
     */
    public function setMaxValue(null|int|float|string|bool|ArgumentInterface $maxValue): static
    {
        $this->maxValue = $maxValue instanceof ArgumentInterface
            ? $maxValue
            : new Value($maxValue);

        return $this;
    }

    /**
     * Get maximum value for comparison
     */
    public function getMaxValue(): ?ArgumentInterface
    {
        return $this->maxValue;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new LogicException('Identifier must be specified');
        }

        if (! $this->minValue instanceof ArgumentInterface) {
            throw new LogicException('minValue must be specified');
        }

        if (! $this->maxValue instanceof ArgumentInterface) {
            throw new LogicException('maxValue must be specified');
        }

        $identifierSpec = $this->identifier->getSpecification();
        $minValueSpec   = $this->minValue->getSpecification();
        $maxValueSpec   = $this->maxValue->getSpecification();
        $spec           = "{$identifierSpec} {$this->operator} {$minValueSpec} AND {$maxValueSpec}";

        return [
            'spec'   => $this->specification ?? $spec,
            'values' => [$this->identifier, $this->minValue, $this->maxValue],
        ];
    }
}
