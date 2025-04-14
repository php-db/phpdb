<?php

namespace LaminasIntegrationTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Driver\Pdo;
use Laminas\Db\Adapter\Platform\Sqlite;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function getenv;

#[Group('integration')]
#[Group('integration-sqlite')]
final class SqliteTest extends TestCase
{
    /** @var array<string, resource|\PDO> */
    public array|\PDO $adapters = [];

    #[Override]
    protected function setUp(): void
    {
        if (! getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_SQLITE_MEMORY')) {
            $this->markTestSkipped(self::class . ' integration tests are not enabled!');
        }
        if (extension_loaded('pdo')) {
            $this->adapters['pdo_sqlite'] = new \PDO(
                'sqlite::memory:'
            );
        }
    }

    /**
     * @return void
     */
    public function testQuoteValueWithPdoSqlite()
    {
        if (! $this->adapters['pdo_sqlite'] instanceof \PDO) {
            $this->markTestSkipped('SQLite (PDO_SQLITE) not configured in unit test configuration file');
        }
        $sqlite = new Sqlite($this->adapters['pdo_sqlite']);
        $value  = $sqlite->quoteValue('value');
        self::assertEquals('\'value\'', $value);

        $sqlite = new Sqlite(new Pdo\Pdo(new Pdo\Connection($this->adapters['pdo_sqlite'])));
        $value  = $sqlite->quoteValue('value');
        self::assertEquals('\'value\'', $value);
    }
}
