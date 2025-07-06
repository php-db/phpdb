<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Binary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Binary::class, 'getExpressionData')]
final class BinaryTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Binary('foo', 10000000);
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'BINARY(10000000)'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
