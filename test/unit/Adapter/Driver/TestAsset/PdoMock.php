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
     */
    #[ReturnTypeWillChange]
    public function getAttribute($attribute): null
    {
        return null;
    }

    public function rollBack(): bool
    {
        return true;
    }
}
