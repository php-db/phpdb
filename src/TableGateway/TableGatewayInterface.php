<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Where;

interface TableGatewayInterface
{
    /** @return string */
    public function getTable();

    public function select(Where|Closure|string|array $where): ResultSetInterface;

    /**
     * @param array<string, mixed> $set
     */
    public function insert(array $set): int;

    /**
     * @param array<string, mixed> $set
     */
    public function update(array $set, Where|Closure|array|string $where): int;

    public function delete(Where|Closure|array|string $where): int;
}
