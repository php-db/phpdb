<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Select;

class In extends AbstractExpression implements PredicateInterface
{
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $valueSet   = null;
    protected string             $operator   = 'IN';

    /**
     * Constructor
     *
     * @param list<bool|string|float|int|null>|Select|ArgumentInterface|null $valueSet
     */
    public function __construct(
        string|ArgumentInterface|null $identifier = null,
        array|Select|ArgumentInterface|null $valueSet = null,
    ) {
        if (null !== $identifier) {
            $this->setIdentifier($identifier);
        }

        if (null !== $valueSet) {
            $this->setValueSet($valueSet);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingIdentifier();
        }

        if (! $this->valueSet instanceof ArgumentInterface) {
            throw InvalidArgumentException::forMissingValueSet();
        }

        $identifierSpec = $this->identifier->getSpecification();
        $valueSetSpec   = $this->valueSet->getSpecification();

        return [
            'spec'   => $this->specification ?? "{$identifierSpec} {$this->operator} {$valueSetSpec}",
            'values' => [$this->identifier, $this->valueSet],
        ];
    }

    /**
     * Get identifier of comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Gets set of values in IN comparison
     */
    public function getValueSet(): ?ArgumentInterface
    {
        return $this->valueSet;
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
     * Set set of values for IN comparison
     *
     * @param list<bool|string|float|int|null>|Select|ArgumentInterface $valueSet
     */
    public function setValueSet(array|Select|ArgumentInterface $valueSet): static
    {
        if ($valueSet instanceof ArgumentInterface) {
            $this->valueSet = $valueSet;
        } elseif ($valueSet instanceof Select) {
            $this->valueSet = new ArgumentSelect($valueSet);
        } else {
            $this->valueSet = new Values($valueSet);
        }

        return $this;
    }
}
