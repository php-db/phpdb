<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\AbstractLengthColumn;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
final class AbstractLengthColumnTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)->setConstructorArgs(['foo', 55])->onlyMethods([])->getMock();
        self::assertEquals(55, $column->getLength());
        self::assertSame($column, $column->setLength(20));
        self::assertEquals(20, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetLength(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)->setConstructorArgs(['foo', 55])->onlyMethods([])->getMock();
        self::assertEquals(55, $column->getLength());
    }

    /**
     * @throws Exception
     */
    public function testGetExpressionData(): void
    {
        $column = $this->getMockBuilder(AbstractLengthColumn::class)->setConstructorArgs(['foo', 4])->onlyMethods([])->getMock();

        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'INTEGER(4)'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
