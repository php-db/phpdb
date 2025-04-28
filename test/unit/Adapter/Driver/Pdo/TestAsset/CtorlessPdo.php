<?php

namespace LaminasTest\Db\Adapter\Driver\Pdo\TestAsset;

use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;

class CtorlessPdo extends PDO
{
    public function __construct(protected PDOStatement&MockObject $mockStatement)
    {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function prepare(string $query, $options = null): PDOStatement
    {
        return $this->mockStatement;
    }
}
