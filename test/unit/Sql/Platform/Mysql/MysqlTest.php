<?php

namespace PhpDbTest\Sql\Platform\Mysql;

use PhpDb\Sql\Platform\Mysql\Mysql;
use PhpDb\Sql\Platform\Mysql\SelectDecorator;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function current;
use function key;

#[CoversMethod(Mysql::class, '__construct')]
class MysqlTest extends TestCase
{
    #[TestDox('unit test / object test: Test Mysql object has Select proxy')]
    public function testConstruct(): void
    {
        $mysql      = new Mysql();
        $decorators = $mysql->getDecorators();

        $type      = key($decorators);
        $decorator = current($decorators);
        self::assertEquals(Select::class, $type);
        self::assertInstanceOf(SelectDecorator::class, $decorator);
    }
}
