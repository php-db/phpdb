<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

/**
 * @api
 * @psalm-import-type ColumnValue from AbstractSql
 * @psalm-import-type GatewayTable from AbstractTableGateway
 * @psalm-import-type GatewayWhere from AbstractTableGateway
 */
interface TableGatewayInterface
{
    /** @param GatewayWhere $where */
    public function delete(Where|Closure|array|string $where): int;

    /** @return GatewayTable */
    public function getTable(): TableIdentifier|string|array;

    /**
     * @param array<string, ColumnValue> $set
     */
    public function insert(array $set): int;

    /** @param GatewayWhere|null $where */
    public function select(Where|Closure|string|array|null $where = null): ResultSetInterface;

    /**
     * @param array<string, ColumnValue> $set
     * @param GatewayWhere               $where
     */
    public function update(array $set, Where|Closure|array|string $where): int;
}
