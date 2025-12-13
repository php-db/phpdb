<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PDO;

class PdoStubDriver extends PDO
{
    #[Override] public function beginTransaction(): bool
    {
        return true;
    }

    #[Override] public function commit(): bool
    {
        return true;
    }

    /**
     * @param string $user
     * @param string $password
     */
    public function __construct(string $dsn, $user, $password)
    {
    }

    #[Override] public function rollBack(): bool
    {
        return true;
    }
}
