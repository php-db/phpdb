<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\TestAsset;

use PDO;
use ReturnTypeWillChange;

/**
 * Stub class
 */
final class PdoMock extends PDO
{
    public function __construct()
    {
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    /**
     * @param string $attribute
     * @return null
     */
    #[ReturnTypeWillChange]
    public function getAttribute($attribute)
    {
        return null;
    }

    public function rollBack(): bool
    {
        return true;
    }
}
