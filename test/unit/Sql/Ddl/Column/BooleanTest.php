<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Boolean;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Boolean::class, 'getExpressionData')]
#[CoversClass(Boolean::class)]
final class BooleanTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Boolean('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('BOOLEAN'),
        ], $expressionData->getExpressionValues());
    }

    #[Group('6257')]
    public function testIsAlwaysNotNullable(): void
    {
        $column = new Boolean('foo', true);

        self::assertFalse($column->isNullable());

        $column->setNullable(true);

        self::assertFalse($column->isNullable());
    }
}
