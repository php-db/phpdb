<?php

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionPart;

use Override;

use function array_fill;
use function count;
use function implode;
use function sprintf;

abstract class AbstractConstraint implements ConstraintInterface
{
    protected string $columnSpecification = '(%s)';

    protected string $namedSpecification = 'CONSTRAINT %s';

    protected string $specification = '';

    protected string $name = '';

    protected array $columns = [];

    public function __construct(null|array|string $columns = null, ?string $name = null)
    {
        if ($columns !== null) {
            $this->setColumns($columns);
        }

        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setColumns(string|array $columns): static
    {
        $this->columns = (array) $columns;

        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function addColumn(string $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * {}
     */
    #[Override] public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * {}
     */
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $expressionPart = new ExpressionPart();

        if ($this->name !== '') {
            $expressionPart->addSpecification($this->namedSpecification);
            $expressionPart->addValue(new Argument($this->name, ArgumentType::Identifier));
        }

        if ($this->specification !== '') {
            $expressionPart->addSpecification($this->specification);
        }

        $columnCount = count($this->columns);
        if ($columnCount !== 0) {
            $columnSpecification = array_fill(0, $columnCount, '%s');
            $columnSpecification = sprintf($this->columnSpecification, implode(', ', $columnSpecification));
            $expressionPart->addSpecification($columnSpecification);
            for ($i = 0; $i < $columnCount; $i++) {
                $expressionPart->addValue(new Argument($this->columns[$i], ArgumentType::Identifier));
            }
        }

        return new ExpressionData($expressionPart);
    }
}
