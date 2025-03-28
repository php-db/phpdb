<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Mysql;

use Exception;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_fill;

/**
 * Usually mysql has 151 max connections by default.
 * Set up a test where executed Laminas\Db\Adapter\Adapter::query and then using table gateway to fetch a row
 * On tear down disconnected from the database and set the driver adapter on null
 * Running many tests ended up in consuming all mysql connections and not releasing them
 */
class TableGatewayAndAdapterTest extends TestCase
{
    use AdapterTrait;

    /**
     * @throws Exception
     */
    #[DataProvider('connections')]
    public function testGetOutOfConnections(): void
    {
        $this->adapter->query('SELECT VERSION();');
        $table  = new TableGateway(
            'test',
            $this->adapter
        );
        $select = $table->getSql()->select()->where(['name' => 'foo']);
        $result = $table->selectWith($select);
        self::assertCount(3, $result->current());
    }

    protected function tearDown(): void
    {
        if ($this->adapter->getDriver()->getConnection()->isConnected()) {
            $this->adapter->getDriver()->getConnection()->disconnect();
        }
        $this->adapter = null;
    }

    public static function connections(): array
    {
        return array_fill(0, 200, []);
    }
}
