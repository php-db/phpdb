<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;

use function array_fill;
use function count;
use function implode;
use function str_replace;

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

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setColumns(string|array $columns): static
    {
        $this->columns = (array) $columns;

        return $this;
    }

    public function addColumn(string $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    #[Override] public function getColumns(): array
    {
        return $this->columns;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $specParts = [];
        $values    = [];

        if ($this->name !== '') {
            $specParts[] = $this->namedSpecification;
            $values[]    = new Identifier($this->name);
        }

        if ($this->specification !== '') {
            $specParts[] = $this->specification;
        }

        $columnCount = count($this->columns);
        if ($columnCount !== 0) {
            $columnSpec  = array_fill(0, $columnCount, '%s');
            $specParts[] = str_replace('%s', implode(', ', $columnSpec), $this->columnSpecification);
            for ($i = 0; $i < $columnCount; $i++) {
                $values[] = new Identifier($this->columns[$i]);
            }
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $values,
        ];
    }
}
