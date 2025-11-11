<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\Blob;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Blob::class, 'getExpressionData')]
final class BlobTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Blob('foo');
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'BLOB'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
