<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\Check;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Check::class, '__construct')]
#[CoversMethod(Check::class, 'getExpressionData')]
#[Group('unit')]
final class CheckTest extends TestCase
{
    /**
     * Asserts what a table-level constraint renders to inside CREATE TABLE, with values quoted.
     */
    private static function assertConstraintRenders(string $expected, ConstraintInterface $constraint): void
    {
        $createTable = new CreateTable('t');
        $createTable->addConstraint($constraint);

        static::assertSame(
            "CREATE TABLE \"t\" ( \n    {$expected} \n)",
            $createTable->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getExpressionDataMergesExpressionSpecAndValues(): void
    {
        $check = new Check(new Expression('a > ?', [1]), 'chk');

        $expressionData = $check->getExpressionData();

        static::assertSame('CONSTRAINT %s CHECK (a > %s)', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('chk'),
                Argument::value(1),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersColumnLevelCheckInline(): void
    {
        $column = new Integer('age');
        $column->addConstraint(new Check('age >= 0', 'chk_age'));

        $createTable = new CreateTable('t');
        $createTable->addColumn($column);

        static::assertSame(
            "CREATE TABLE \"t\" ( \n    \"age\" INTEGER NOT NULL CONSTRAINT \"chk_age\" CHECK (age >= 0) \n)",
            $createTable->getSqlString(),
        );
    }

    #[Test]
    public function rendersExpressionIdentifiersQuoted(): void
    {
        $check = new Check(new Expression('? > ?', [new Identifier('a'), new Identifier('b')]), 'chk');

        static::assertConstraintRenders('CONSTRAINT "chk" CHECK ("a" > "b")', $check);
    }

    #[Test]
    public function rendersExpressionValuesQuoted(): void
    {
        $check = new Check(new Expression('a > ?', [1]), 'chk');

        static::assertConstraintRenders('CONSTRAINT "chk" CHECK (a > \'1\')', $check);
    }

    #[Test]
    public function rendersPercentSignsInExpressionVerbatimWhenNamed(): void
    {
        $check = new Check(new Expression("email LIKE '%@%'"), 'chk');

        static::assertConstraintRenders('CONSTRAINT "chk" CHECK (email LIKE \'%@%\')', $check);
    }

    #[Test]
    public function rendersPercentSignsInExpressionVerbatimWhenUnnamed(): void
    {
        $check = new Check(new Expression("email LIKE '%@%'"));

        static::assertConstraintRenders('CHECK (email LIKE \'%@%\')', $check);
    }

    #[Test]
    public function rendersWithoutConstraintClauseWhenUnnamed(): void
    {
        static::assertConstraintRenders('CHECK (id > 0)', new Check('id > 0'));
    }

    public function testGetExpressionData(): void
    {
        $check = new Check('id>0', 'foo');

        $expressionData = $check->getExpressionData();

        self::assertEquals('CONSTRAINT %s CHECK (%s)', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('id>0'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function throwsWhenExpressionIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Check constraint expression must not be an empty string.');

        new Check('');
    }
}
