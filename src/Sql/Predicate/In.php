<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Select;

use function array_fill;
use function count;
use function implode;

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
     *
     * @return $this Provides a fluent interface
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

        $identifierSpec = $this->getIdentifierSpecification();
        $valueSetSpec   = $this->getValueSetSpecification();

        return [
            'spec'   => "{$identifierSpec} {$this->operator} {$valueSetSpec}",
            'values' => [$this->identifier, $this->valueSet],
        ];
    }

    /**
     * Build specification string for identifier
     */
    protected function getIdentifierSpecification(): string
    {
        if ($this->identifier instanceof Identifier) {
            return '%s';
        }

        // Handle array identifiers for multi-column IN: (col1, col2) IN ...
        if ($this->identifier instanceof Identifiers) {
            $count = count($this->identifier->getValue());
            return $count > 0
                ? '(' . implode(', ', array_fill(0, $count, '%s')) . ')'
                : '(NULL)';
        }

        return '%s';
    }

    /**
     * Build specification string for value set
     */
    protected function getValueSetSpecification(): string
    {
        if ($this->valueSet instanceof ArgumentSelect) {
            return '%s';
        }

        if ($this->valueSet instanceof Values) {
            $values = $this->valueSet->getValue();
            $count  = count($values);
            return $count > 0
                ? '(' . implode(', ', array_fill(0, $count, '%s')) . ')'
                : '(NULL)';
        }

        return '%s';
    }
}
