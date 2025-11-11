<?php

namespace PhpDbTest\Sql\Platform\Oracle;

use PhpDb\Sql\Platform\Oracle\Oracle;
use PhpDb\Sql\Platform\Oracle\SelectDecorator;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function current;
use function key;

#[CoversMethod(Oracle::class, '__construct')]
class OracleTest extends TestCase
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
