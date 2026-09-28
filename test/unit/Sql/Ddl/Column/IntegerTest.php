<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Integer::class, 'getExpressionData')]
#[CoversMethod(Integer::class, 'normaliseDisplayWidth')]
#[CoversMethod(Column::class, 'getExpressionData')]
#[Group('unit')]
final class IntegerTest extends TestCase
{
    /**
     * @return array<string, array{bool|int|string}>
     */
    public static function invalidLengthProvider(): array
    {
        return [
            'boolean'          => [true],
            'negative integer' => [-1],
            'non-digit string' => ['abc'],
        ];
    }

    /**
     * Asserts what a column renders to inside CREATE TABLE, with values quoted.
     */
    private static function assertColumnRenders(string $expected, ColumnInterface $column): void
    {
        $createTable = new CreateTable('t');
        $createTable->addColumn($column);

        static::assertSame(
            "CREATE TABLE \"t\" ( \n    {$expected} \n)",
            $createTable->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getExpressionDataPlacesLengthDirectlyAfterType(): void
    {
        $expressionData = (new Integer('i', false, null, ['length' => 11]))->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('i'),
                Argument::literal('INTEGER'),
                Argument::literal('11'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersLengthBeforeNullabilityAndDefault(): void
    {
        static::assertColumnRenders(
            '"i" INTEGER(11) NULL DEFAULT \'7\'',
            new Integer('i', true, 7, ['length' => 11]),
        );
    }

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"i" INTEGER(11) NOT NULL', new Integer('i', false, null, ['length' => 11]));
    }

    #[Test]
    public function rendersLengthSetAsIntegerOption(): void
    {
        $column = new Integer('i');
        $column->setOption('length', 11);

        static::assertColumnRenders('"i" INTEGER(11) NOT NULL', $column);
    }

    public function testGetExpressionData(): void
    {
        $column = new Integer('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );

        $column = new Integer('foo');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL PRIMARY KEY', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );
    }

    public function testGetExpressionDataExcludesLengthWhenNotSet(): void
    {
        $column = new Integer('id');

        $expressionData = $column->getExpressionData();

        self::assertStringNotContainsString('(', $expressionData['spec']);
    }

    public function testGetExpressionDataIncludesLengthWhenOptionSet(): void
    {
        $column = new Integer('id');
        $column->setOption('length', '11');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('id'),
                Argument::literal('INTEGER'),
                Argument::literal('11'),
            ],
            $expressionData['values'],
        );
    }

    public function testObjectConstruction(): void
    {
        $integer = new Integer('foo');
        self::assertEquals('foo', $integer->getName());
    }

    #[Test]
    #[DataProvider('invalidLengthProvider')]
    public function throwsWhenLengthOptionIsNotANonNegativeInteger(bool|int|string $length): void
    {
        $column = new Integer('i', false, null, ['length' => $length]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "i" length option must be a non-negative integer');

        $column->getExpressionData();
    }
}
