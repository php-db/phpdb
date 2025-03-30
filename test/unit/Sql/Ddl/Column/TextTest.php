<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Text;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'getExpressionData')]
class TextTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Text('foo');
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'TEXT'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
