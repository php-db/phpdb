<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;

use function array_fill;
use function count;
use function implode;

class ForeignKey extends AbstractConstraint
{
    protected string $onDeleteRule = 'NO ACTION';

    protected string $onUpdateRule = 'NO ACTION';

    protected string $referenceTable = '';

    protected string $columnSpecification = 'FOREIGN KEY (%s)';

    /** @var string[] */
    protected array $referenceColumn = [];

    /** @var string[] */
    protected array $referenceSpecification = [
        'REFERENCES %s',
        'ON DELETE %s ON UPDATE %s',
    ];

    /**
     * @param string[]|string|null $referenceColumn
     */
    public function __construct(
        string $name,
        string|array $columns,
        string $referenceTable,
        array|string|null $referenceColumn,
        null|string $onDeleteRule = null,
        null|string $onUpdateRule = null
    ) {
        parent::__construct($columns, $name);

        $this->setReferenceTable($referenceTable);

        if ($referenceColumn !== null) {
            $this->setReferenceColumn($referenceColumn);
        }

        if ($onDeleteRule !== null) {
            $this->setOnDeleteRule($onDeleteRule);
        }

        if ($onUpdateRule !== null) {
            $this->setOnUpdateRule($onUpdateRule);
        }
    }

    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    public function setReferenceTable(string $referenceTable): static
    {
        $this->referenceTable = $referenceTable;

        return $this;
    }

    public function getReferenceColumn(): array
    {
        return $this->referenceColumn;
    }

    /**
     * @param string[]|string $referenceColumn
     */
    public function setReferenceColumn(array|string $referenceColumn): static
    {
        $this->referenceColumn = (array) $referenceColumn;

        return $this;
    }

    public function getOnDeleteRule(): string
    {
        return $this->onDeleteRule;
    }

    public function setOnDeleteRule(string $onDeleteRule): static
    {
        $this->onDeleteRule = $onDeleteRule;

        return $this;
    }

    public function getOnUpdateRule(): string
    {
        return $this->onUpdateRule;
    }

    public function setOnUpdateRule(string $onUpdateRule): static
    {
        $this->onUpdateRule = $onUpdateRule;

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $expressionData = parent::getExpressionData();
        $colCount       = count($this->referenceColumn);

        $expressionData['spec']    .= ' ' . $this->referenceSpecification[0];
        $expressionData['values'][] = new Identifier($this->referenceTable);

        if ($colCount !== 0) {
            $expressionData['spec'] .= ' (' . implode(', ', array_fill(0, $colCount, '%s')) . ')';
            foreach ($this->referenceColumn as $column) {
                $expressionData['values'][] = new Identifier($column);
            }
        }

        $expressionData['spec']    .= ' ' . $this->referenceSpecification[1];
        $expressionData['values'][] = new Literal($this->onDeleteRule);
        $expressionData['values'][] = new Literal($this->onUpdateRule);

        return $expressionData;
    }
}
