<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\Time;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Time::class, 'getExpressionData')]
final class TimeTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $column = new Time('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('TIME'),
            ],
            $expressionData['values'],
        );
    }
}
