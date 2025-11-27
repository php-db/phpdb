<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Ddl\Column\Date;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Date::class, 'getExpressionData')]
final class DateTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Date('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('DATE'),
        ], $expressionData->getExpressionValues());
    }
}
