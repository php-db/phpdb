<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\PreparableSqlBuilder;
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
     */
    public function toColumnsSql(PreparableSqlBuilder $builder): string
    {
        $q      = $builder->q;
        $prefix = $q . $this->name->getReference() . $q . '.';
        $result = '';

        foreach ($this->columns as $alias => $column) {
            if (is_string($column)) {
                if ($column !== Select::SQL_STAR) {
                    $result .= is_string($alias)
                        ? ', ' . $prefix . $q . $column . $q . ' AS ' . $q . $alias . $q
                        : ', ' . $prefix . $q . $column . $q . ' AS ' . $q . $column . $q;
                } else {
                    $result .= ', ' . $prefix . '*';
                }
            } elseif ($column instanceof ExpressionInterface) {
                $col     = $builder->processExpression($column);
                $result .= is_string($alias) ? ', ' . $col . ' AS ' . $q . $alias . $q : ', ' . $col;
            } elseif ($column instanceof Select) {
                $col     = $builder->processSubSelect($column);
                $result .= is_string($alias) ? ', (' . $col . ') AS ' . $q . $alias . $q : ', (' . $col . ')';
            }
        }

        return $result;
    }
}
