<?php

namespace PhpDbTest\Adapter\Driver\TestAsset;

use Override;
use PDO;
use ReturnTypeWillChange;

/**
 * Stub class
 */
class PdoMock extends PDO
{
    public function __construct()
    {
    }

    #[Override] public function beginTransaction(): bool
    {
        return true;
    }

    #[Override] public function commit(): bool
    {
        return true;
    }

    /**
     * @param string $attribute
     * @return null
     */
    #[Override] #[ReturnTypeWillChange]
    public function getAttribute($attribute)
    {
        return null;
    }

    #[Override] public function rollBack(): bool
    {
        return true;
    }
}
