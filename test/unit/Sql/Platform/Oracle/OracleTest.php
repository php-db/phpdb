<?php

namespace LaminasTest\Db\Sql\Platform\Oracle;

use Laminas\Db\Sql\Platform\Oracle\Oracle;
use Laminas\Db\Sql\Platform\Oracle\SelectDecorator;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function current;
use function key;

#[CoversMethod(Oracle::class, '__construct')]
final class OracleTest extends TestCase
{
    #[TestDox('unit test / object test: Test Mysql object has Select proxy')]
    public function testConstruct(): void
    {
        $oracle     = new Oracle();
        $decorators = $oracle->getDecorators();

        $type      = key($decorators);
        $decorator = current($decorators);
        self::assertEquals(Select::class, $type);
        self::assertInstanceOf(SelectDecorator::class, $decorator);
    }
}
