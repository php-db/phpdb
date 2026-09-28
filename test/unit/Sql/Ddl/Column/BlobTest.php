<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\Blob;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Blob::class, 'getExpressionData')]
#[CoversMethod(Blob::class, 'getLengthExpression')]
#[Group('unit')]
final class BlobTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionDataOmitsConfiguredLength(): void
    {
        $expressionData = (new Blob('foo', 16_777_215))->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Literal('BLOB'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersWithoutConfiguredLength(): void
    {
        static::assertColumnRenders('"foo" BLOB NOT NULL', new Blob('foo', 16_777_215));
    }

    public function testGetExpressionData(): void
    {
        $column = new Blob('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                new Identifier('foo'),
                new Literal('BLOB'),
            ],
            $expressionData['values'],
        );
    }
}
