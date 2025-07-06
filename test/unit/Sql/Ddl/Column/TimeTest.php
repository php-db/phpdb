<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Time;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Time::class, 'getExpressionData')]
final class TimeTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Time('foo');
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'TIME'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
