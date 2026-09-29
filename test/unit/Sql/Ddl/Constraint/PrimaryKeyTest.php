<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(PrimaryKey::class, 'getExpressionData')]
final class PrimaryKeyTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionData(): void
    {
        $pk = new PrimaryKey('foo');

        $expressionData = $pk->getExpressionData();

        static::assertSame('PRIMARY KEY (%s)', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
            ],
            $expressionData['values'],
        );
    }
}
