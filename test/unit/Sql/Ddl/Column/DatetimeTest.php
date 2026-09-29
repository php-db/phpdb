<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Datetime;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Datetime::class, 'getExpressionData')]
final class DatetimeTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Datetime('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DATETIME'),
            ],
            $expressionData['values'],
        );
    }
}
