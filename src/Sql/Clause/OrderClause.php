<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\OrderItem;

use function count;
use function explode;
use function is_int;
use function is_string;
use function preg_split;
use function str_contains;
use function strcasecmp;
use function trim;

final class OrderClause implements ClauseInterface
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    /** @var OrderItem[] */
    protected array $items = [];

    public function add(ExpressionInterface|array|string $order): static
    {
        if ($order instanceof ExpressionInterface) {
            $this->items[] = OrderItem::fromExpression($order);
            return $this;
        }

        if (is_string($order)) {
            $order = str_contains($order, ',') ? preg_split('#,\s+#', $order) : [$order];
        }

        foreach ($order as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $this->items[] = OrderItem::fromExpression($v);
                continue;
            }

            if (is_int($k)) {
                if (str_contains($v, ' ')) {
                    [$k, $v] = explode(' ', $v, 2);
                } else {
                    $k = $v;
                    $v = self::ORDER_ASCENDING;
                }
            }

            $direction = strcasecmp(trim($v), self::ORDER_DESCENDING) === 0
                ? self::ORDER_DESCENDING
                : self::ORDER_ASCENDING;

            $this->items[] = OrderItem::create($k, $direction);
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
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function prepareSqlString(string $q): string
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
            $sql  .= $item->toSql($q);
            $first = false;
        }

        return $sql;
    }
}