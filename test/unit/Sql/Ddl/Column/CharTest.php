<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Char;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Char::class, 'getExpressionData')]
final class CharTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Char('foo', 20);
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'CHAR(20)'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
