<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\Select;

class In extends AbstractExpression implements PredicateInterface
{
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $valueSet   = null;
    protected string $operator               = 'IN';

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|array|Select|ArgumentInterface $valueSet = null
    ) {
        if ($identifier !== null) {
            $this->identifier = $identifier instanceof ArgumentInterface
                ? $identifier
                : new Identifier($identifier);
        }

        if ($valueSet !== null) {
            if ($valueSet instanceof ArgumentInterface) {
                $this->valueSet = $valueSet;
            } elseif ($valueSet instanceof Select) {
                $this->valueSet = new ArgumentSelect($valueSet);
            } else {
                $this->valueSet = new Values($valueSet);
            }
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
     * Get identifier of comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set set of values for IN comparison
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

    /**
     * Gets set of values in IN comparison
     */
    public function getValueSet(): ?ArgumentInterface
    {
        return $this->valueSet;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->valueSet instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Value set must be provided for IN predicate');
        }

        return [
            'spec'   => $this->specification ?? "%s {$this->operator} %s",
            'values' => [$this->identifier, $this->valueSet],
        ];
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->valueSet instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Value set must be provided for IN predicate');
        }

        // Fast path: Values (most common) already includes parentheses
        // ArgumentSelect needs wrapping
        $valueSetSql = ! $this->valueSet instanceof ArgumentSelect
            ? $builder->argumentToSql($this->valueSet)
            : '(' . $builder->argumentToSql($this->valueSet) . ')';

        $identifierSql = $builder->argumentToSql($this->identifier);

        return "{$identifierSql} {$this->operator} {$valueSetSql}";
    }
}
