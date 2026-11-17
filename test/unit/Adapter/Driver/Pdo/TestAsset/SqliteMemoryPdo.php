<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;

use function implode;
use function sprintf;

class SqliteMemoryPdo extends PDO
{
    protected MockObject&PDOStatement $mockStatement;

    /**
     * @throws Exception
     */
    public function __construct(?string $sql = null)
    {
        parent::__construct('sqlite::memory:');

        if (null === $sql || '' === $sql) {
            return;
        }

        if (false === $this->exec($sql)) {
            throw new Exception(sprintf(
                'Error: %s, %s',
                $this->errorCode(),
                implode(',', $this->errorInfo()),
            ));
        }
    }
}
