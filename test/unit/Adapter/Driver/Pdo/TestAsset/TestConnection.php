<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use Override;
use PDO;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;

use function sprintf;

/**
 * Test asset for AbstractPdoConnection - provides a concrete implementation for testing
 */
final class TestConnection extends AbstractPdoConnection
{
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource instanceof PDO) {
            return $this;
        }

        // Build DSN if not already set
        if (! isset($this->dsn)) {
            $this->dsn = $this->buildDsn();
        }

        $this->resource = new PDO(
            $this->getDsn(),
            $this->connectionParameters['username'] ?? null,
            $this->connectionParameters['password'] ?? null
        );
        return $this;
    }

    private function buildDsn(): string
    {
        $pdoDriver = $this->connectionParameters['pdodriver'] ?? 'sqlite';
        $database  = $this->connectionParameters['database'] ?? ':memory:';

        return match ($pdoDriver) {
            'sqlite' => "sqlite:{$database}",
            'mysql' => sprintf(
                'mysql:host=%s;dbname=%s',
                $this->connectionParameters['hostname'] ?? 'localhost',
                $database
            ),
            default => "{$pdoDriver}:{$database}",
        };
    }

    /**
     * @param string|null $name
     */
    #[Override]
    public function getLastGeneratedValue($name = null): string|int|bool|null
    {
        return $this->resource?->lastInsertId($name) ?? null;
    }

    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        // For SQLite and other PDO drivers, return database name or false
        return $this->connectionParameters['database'] ?? false;
    }
}
