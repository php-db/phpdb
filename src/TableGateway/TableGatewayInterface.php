<?php

declare(strict_types=1);

namespace PhpDb\TableGateway;

use Closure;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

interface TableGatewayInterface
{
    public function getTable(): TableIdentifier|string|array;

    public function select(Where|Closure|string|array|null $where = null): ResultSetInterface;

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
