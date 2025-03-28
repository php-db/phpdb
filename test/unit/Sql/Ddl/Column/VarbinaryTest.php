<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Varbinary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varbinary::class, 'getExpressionData')]
class VarbinaryTest extends TestCase
{
    public function testGetExpressionData()
    {
        $column = new Varbinary('foo', 20);
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'VARBINARY(20)'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
