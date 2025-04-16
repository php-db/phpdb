<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\ExpressionData;

use function array_fill;
use function count;
use function implode;
use function sprintf;

class ForeignKey extends AbstractConstraint
{
    protected string $onDeleteRule        = 'NO ACTION';
    protected string $onUpdateRule        = 'NO ACTION';
    protected string $referenceTable      = '';
    protected string $columnSpecification = 'FOREIGN KEY (%s)';

    /** @var string[] */
    protected array $referenceColumn = [];

    /** @var string[] */
    protected array $referenceSpecification = [
        'REFERENCES %s',
        'ON DELETE %s ON UPDATE %s',
    ];

    /**
     * @param string            $name
     * @param string|array      $columns
     * @param string            $referenceTable
     * @param string[]|string|null $referenceColumn
     * @param string|null       $onDeleteRule
     * @param string|null       $onUpdateRule
     */
    public function __construct(
        string $name,
        string|array $columns,
        string $referenceTable,
        array|string|null $referenceColumn,
        string $onDeleteRule = null,
        string $onUpdateRule = null
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

    /**
     * @return string
     */
    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    /**
     * @param string $referenceTable
     * @return $this Provides a fluent interface
     */
    public function setReferenceTable(string $referenceTable): static
    {
        $this->referenceTable = $referenceTable;

        return $this;
    }

    /**
     * @return array
     */
    public function getReferenceColumn(): array
    {
        return $this->referenceColumn;
    }

    /**
     * @param string[]|string $referenceColumn
     * @return $this Provides a fluent interface
     */
    public function setReferenceColumn(array|string $referenceColumn): static
    {
        $this->referenceColumn = (array) $referenceColumn;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnDeleteRule(): string
    {
        return $this->onDeleteRule;
    }

    /**
     * @param string $onDeleteRule
     * @return $this Provides a fluent interface
     */
    public function setOnDeleteRule(string $onDeleteRule): static
    {
        $this->onDeleteRule = $onDeleteRule;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnUpdateRule(): string
    {
        return $this->onUpdateRule;
    }

    /**
     * @param string $onUpdateRule
     * @return $this Provides a fluent interface
     */
    public function setOnUpdateRule(string $onUpdateRule): static
    {
        $this->onUpdateRule = $onUpdateRule;

        return $this;
    }

    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $colCount = count($this->referenceColumn);

        $expressionData = parent::getExpressionData();

        $expressionPart = $expressionData->getExpressionPart(0);
        $expressionPart
            ->addSpecification($this->referenceSpecification[0])
            ->addValue(new Argument($this->referenceTable, ArgumentType::Identifier));

        if ($colCount) {
            $expressionPart->addSpecification(sprintf(
                '(%s)',
                implode(', ', array_fill(0, $colCount, '%s'))
            ));
            foreach ($this->referenceColumn as $column) {
                $expressionPart->addValue(new Argument($column, ArgumentType::Identifier));
            }
        }

        $expressionPart
            ->addSpecification($this->referenceSpecification[1])
            ->addValue(new Argument($this->onDeleteRule, ArgumentType::Literal))
            ->addValue(new Argument($this->onUpdateRule, ArgumentType::Literal));

        return $expressionData;
    }
}
