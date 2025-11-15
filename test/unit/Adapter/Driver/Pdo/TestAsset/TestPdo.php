<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use Override;
use PDO;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;

use function ucfirst;

/**
 * Test asset for AbstractPdo - provides a concrete implementation for testing
 */
final class TestPdo extends AbstractPdo
{
    public function __construct(array|AbstractPdoConnection|PDO $connection, ?Statement $statement = null, ?Result $result = null, array $features = [])
    {
        if (! $connection instanceof AbstractPdoConnection && ! $connection instanceof PDO) {
            $connection = new TestConnection($connection);
        }

        parent::__construct(
            $connection,
            $statement ?? new Statement(),
            $result ?? new Result(),
            $features
        );
    }

    /**
     * Create result
     */
    #[Override]
    public function createResult($resource): Result
    {
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue());
        return $result;
    }

    /**
     * Get database platform name
     */
    #[Override]
    public function getDatabasePlatformName(string $nameFormat = self::NAME_FORMAT_CAMELCASE): string
    {
        $pdoDriver = null;
        if ($this->connection instanceof TestConnection) {
            $pdoDriver = $this->connection->getConnectionParameters()['pdodriver'] ?? null;
        }

        if ($pdoDriver === null && $this->connection->isConnected()) {
            $pdoDriver = $this->connection->getResource()->getAttribute(PDO::ATTR_DRIVER_NAME);
        }

        return match ($nameFormat) {
            self::NAME_FORMAT_CAMELCASE => match ($pdoDriver) {
                'sqlsrv', 'dblib', 'mssql' => 'SqlServer',
                'mysql' => 'MySql',
                'oci' => 'Oracle',
                'pgsql' => 'PostgreSql',
                'sqlite' => 'Sqlite',
                default => 'Sql92',
            },
            self::NAME_FORMAT_NATURAL => match ($pdoDriver) {
                'sqlsrv', 'dblib', 'mssql' => 'SQLServer',
                'mysql' => 'MySQL',
                'oci' => 'Oracle',
                'pgsql' => 'PostgreSQL',
                'sqlite' => 'SQLite',
                default => 'SQL92',
            },
            default => $pdoDriver !== null ? ucfirst($pdoDriver) : 'SQL92',
        };
    }
}