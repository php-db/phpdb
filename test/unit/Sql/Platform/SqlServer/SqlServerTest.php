<?php

namespace PhpDbTest\Sql\Platform\SqlServer;

use PhpDb\Sql\Platform\SqlServer\SelectDecorator;
use PhpDb\Sql\Platform\SqlServer\SqlServer;
use PhpDb\Sql\Select;
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
