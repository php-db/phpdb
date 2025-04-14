<?php

namespace LaminasTest\Db\Sql\Platform\SqlServer;

use Laminas\Db\Sql\Platform\SqlServer\SelectDecorator;
use Laminas\Db\Sql\Platform\SqlServer\SqlServer;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function current;
use function key;

#[CoversMethod(SqlServer::class, '__construct')]
final class SqlServerTest extends TestCase
{
    #[TestDox('unit test / object test: Test SqlServer object has Select proxy')]
    public function testConstruct(): void
    {
        $sqlServer  = new SqlServer();
        $decorators = $sqlServer->getDecorators();

        $type      = key($decorators);
        $decorator = current($decorators);
        self::assertEquals(Select::class, $type);
        self::assertInstanceOf(SelectDecorator::class, $decorator);
    }
}
