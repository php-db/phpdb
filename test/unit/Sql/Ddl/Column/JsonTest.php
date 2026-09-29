<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Json;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Json::class, 'getExpressionData')]
final class JsonTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionData(): void
    {
        $column = new Json('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('JSON'),
            ],
            $expressionData['values'],
        );
    }
}
