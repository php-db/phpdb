<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Blob;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Blob::class, 'getExpressionData')]
final class BlobTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Blob('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('BLOB'),
        ], $expressionData->getExpressionValues());
    }
}
