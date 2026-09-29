<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(UniqueKey::class, 'getExpressionData')]
final class UniqueKeyTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $uk = new UniqueKey('foo', 'my_uk');

        $expressionData = $uk->getExpressionData();

        static::assertSame('CONSTRAINT %s UNIQUE (%s)', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('my_uk'),
                Argument::identifier('foo'),
            ],
            $expressionData['values'],
        );
    }
}
