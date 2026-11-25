<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use Override;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;

/**
 * A connection whose connect() leaves it without a PDO resource.
 */
final class UnconnectableConnection extends AbstractPdoConnection
{
    #[Override]
    public function connect(): ConnectionInterface
    {
        return $this;
    }

    #[Override]
    public function getCurrentSchema(): string|false
    {
        return false;
    }

    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return null;
    }
}
