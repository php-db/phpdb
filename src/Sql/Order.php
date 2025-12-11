<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function explode;
use function implode;
use function is_int;
use function str_contains;
use function str_replace;
use function strcasecmp;
use function trim;

class Order
{
    public const ORDER_ASCENDING = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    protected array $orders = [];

    public function add(ExpressionInterface|array|string $order): static
    {
        if (is_string($order)) {
            $order = str_contains($order, ',') ? preg_split('#,\s+#', $order) : (array) $order;
        } elseif (!is_array($order)) {
            $order = [$order];
        }

        foreach ($order as $k => $v) {
            if (is_string($k)) {
                $this->orders[$k] = $v;
            } else {
                $this->orders[] = $v;
            }
        }

        return $this;
    }

    public function count(): int
    {
        return count($this->orders);
    }

    /**
     * Build ORDER BY clause with marker-based identifiers for deferred quoting.
     */
    public function toSqlPart(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $lq = PreparableSqlInterface::P_LQUOTE;
        $rq = PreparableSqlInterface::P_RQUOTE;

        $parts = [];
        foreach ($this->orders as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $parts[] = (string) $v;
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

            // Use markers for deferred quoting - handle table.column format
            if (str_contains($k, '.')) {
                $quoted = $lq . str_replace('.', $rq . '.' . $lq, $k) . $rq;
            } else {
                $quoted = $lq . $k . $rq;
            }

            $parts[] = $quoted . ' ' . $direction;
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }
}