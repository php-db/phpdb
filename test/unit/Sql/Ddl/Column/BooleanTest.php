<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Boolean;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Boolean::class, 'getExpressionData')]
#[CoversClass(Boolean::class)]
final class BooleanTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Boolean('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('BOOLEAN'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    #[Group('6257')]
    public function isAlwaysNotNullable(): void
    {
        $column = new Boolean('foo', true);

        static::assertFalse($column->isNullable());

        $column->setNullable(true);

        static::assertFalse($column->isNullable());
    }
}
