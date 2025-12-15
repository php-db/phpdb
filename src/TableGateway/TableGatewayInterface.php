<?php

namespace PhpDb\TableGateway;

use Closure;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Clause\WhereClause;

interface TableGatewayInterface
{
    /** @return string */
    public function getTable();

    /**
     * @param WhereClause|Closure|string|array $where
     * @return ResultSetInterface
     */
    public function select($where = null);

    /**
     * @param array<string, mixed> $set
     * @return int
     */
    public function insert($set);

    /**
     * @param array<string, mixed> $set
     * @param WhereClause|Closure|string|array $where
     * @return int
     */
    public function update($set, $where = null);

    /**
     * @param WhereClause|Closure|string|array $where
     * @return int
     */
    public function delete($where);
}
