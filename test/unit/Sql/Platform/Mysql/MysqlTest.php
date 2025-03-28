<?php

namespace LaminasTest\Db\Sql\Platform\Mysql;

use Laminas\Db\Sql\Platform\Mysql\Mysql;
use Laminas\Db\Sql\Platform\Mysql\SelectDecorator;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function current;
use function key;

#[CoversMethod(Mysql::class, '__construct')]
class MysqlTest extends TestCase
{
    #[TestDox('unit test / object test: Test Mysql object has Select proxy')]
    public function testConstruct()
    {
        $mysql      = new Mysql();
        $decorators = $mysql->getDecorators();

        $type      = key($decorators);
        $decorator = current($decorators);
        self::assertEquals(Select::class, $type);
        self::assertInstanceOf(SelectDecorator::class, $decorator);
    }
}
