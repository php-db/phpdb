<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ClauseInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

use function count;
use function is_int;
use function is_string;
use function preg_split;
use function str_contains;
use function strpos;
use function strtoupper;
use function substr;
use function trim;

final class Order implements ClauseInterface
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    /** @var OrderSpecification[] */
    protected array $items = [];

    public function add(ExpressionInterface|array|string $order): static
    {
        if ($order instanceof ExpressionInterface) {
            $this->items[] = new OrderSpecification('', '', true, $order);
            return $this;
        }

        if (is_string($order)) {
            if (! str_contains($order, ',')) {
                $spacePos = strpos($order, ' ');
                if ($spacePos !== false) {
                    $column    = substr($order, 0, $spacePos);
                    $direction = strtoupper(trim(substr($order, $spacePos + 1)));
                    $this->items[] = new OrderSpecification(
                        $column,
                        $direction === self::ORDER_DESCENDING ? self::ORDER_DESCENDING : self::ORDER_ASCENDING
                    );
                } else {
                    $this->items[] = new OrderSpecification($order, self::ORDER_ASCENDING);
                }
                return $this;
            }
            $order = preg_split('#,\s+#', $order);
        }

        foreach ($order as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $this->items[] = new OrderSpecification('', '', true, $v);
                continue;
            }

            if (is_int($k)) {
                $spacePos = strpos($v, ' ');
                if ($spacePos !== false) {
                    $k = substr($v, 0, $spacePos);
                    $v = substr($v, $spacePos + 1);
                } else {
                    $k = $v;
                    $v = self::ORDER_ASCENDING;
                }
            }

            $direction = strtoupper(trim($v)) === self::ORDER_DESCENDING
                ? self::ORDER_DESCENDING
                : self::ORDER_ASCENDING;

            $this->items[] = new OrderSpecification($k, $direction);
        }

        return $this;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get order items as formatted strings for testing/inspection.
     *
     * @return string[]
     */
    public function getOrders(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->isExpression
                ? $item->column
                : $item->column . ' ' . $item->direction;
        }
        return $result;
    }

    /**
     * Build ORDER BY clause.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if ($this->items === []) {
            return '';
        }

        $sql   = ' ORDER BY ';
        $first = true;
        foreach ($this->items as $item) {
            if (! $first) {
                $sql .= ', ';
            }
            $sql  .= $item->toSql($builder);
            $first = false;
        }

        return $sql;
    }
}
