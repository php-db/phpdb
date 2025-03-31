<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Floating;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Floating::class, 'getExpressionData')]
final class FloatingTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Floating('foo', 10, 5);
        self::assertEquals(
            [
                [
                    '%s %s NOT NULL',
                    ['foo', 'FLOAT(10,5)'],
                    [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL],
                ],
            ],
            $column->getExpressionData()
        );
    }
}
