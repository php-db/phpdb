<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_string;

final readonly class JoinSpecification
{
    public function __construct(
        public TableIdentifier $name,
        public PredicateInterface $on,
        public array $columns,
        public JoinType $type,
    ) {
    }

    /**
     * Build columns SQL for this join's SELECT clause.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toColumnsSql(string $q, ?callable $expressionProcessor = null): string
    {
        $prefix = $q . $this->name->getRef() . $q . '.';
        $result = '';

        foreach ($this->columns as $alias => $column) {
            if ($column === Select::SQL_STAR) {
                $result .= ', ' . $prefix . '*';
            } elseif ($column instanceof ExpressionInterface) {
                $col     = $expressionProcessor ? $expressionProcessor($column) : $column->getExpressionData()['spec'];
                $result .= is_string($alias) ? ', ' . $col . ' AS ' . $q . $alias . $q : ", $col";
            } elseif ($column instanceof Select) {
                $col     = $expressionProcessor ? $expressionProcessor($column) : '';
                $result .= is_string($alias) ? ', (' . $col . ') AS ' . $q . $alias . $q : ", ($col)";
            } elseif (is_string($alias)) {
                $result .= ', ' . $prefix . $q . $column . $q . ' AS ' . $q . $alias . $q;
            } else {
                $result .= ', ' . $prefix . $q . $column . $q . ' AS ' . $q . $column . $q;
            }
        }

        return $result;
    }
}
