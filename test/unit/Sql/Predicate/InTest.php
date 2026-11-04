<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(In::class, '__construct')]
#[CoversMethod(In::class, 'setIdentifier')]
#[CoversMethod(In::class, 'getIdentifier')]
#[CoversMethod(In::class, 'setValueSet')]
#[CoversMethod(In::class, 'getValueSet')]
#[CoversMethod(In::class, 'getExpressionData')]
#[Group('unit')]
final class InTest extends TestCase
{
    /** @return array<string, array{In, string, array<string, mixed>}> */
    public static function customSpecificationPreparedProvider(): array
    {
        return [
            'one value, positional placeholders'  => [
                (new In('id', [1]))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[:where1])',
                ['where1' => 1],
            ],
            'two values, positional placeholders' => [
                (new In('id', [1, 2]))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[:where1, :where2])',
                ['where1' => 1, 'where2' => 2],
            ],
            'three values, plain placeholders'    => [
                (new In('id', [1, 2, 3]))->setSpecification('%s = ANY (ARRAY[%s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[:where1, :where2, :where3])',
                ['where1' => 1, 'where2' => 2, 'where3' => 3],
            ],
            'empty list'                          => [
                (new In('id', []))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[NULL])',
                [],
            ],
        ];
    }

    /** @return array<string, array{In, string}> */
    public static function customSpecificationSqlProvider(): array
    {
        return [
            'one value, positional placeholders'  => [
                (new In('id', [1]))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[\'1\'])',
            ],
            'two values, positional placeholders' => [
                (new In('id', [1, 2]))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[\'1\', \'2\'])',
            ],
            'three values, plain placeholders'    => [
                (new In('id', [1, 2, 3]))->setSpecification('%s = ANY (ARRAY[%s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[\'1\', \'2\', \'3\'])',
            ],
            'list placed before the identifier'   => [
                (new In('id', [1, 2]))->setSpecification('ARRAY[%2$s] @> ARRAY[%1$s]'),
                'SELECT "t".* FROM "t" WHERE ARRAY[\'1\', \'2\'] @> ARRAY["id"]',
            ],
            'empty list'                          => [
                (new In('id', []))->setSpecification('%1$s = ANY (ARRAY[%2$s])'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (ARRAY[NULL])',
            ],
            'sub-select value set'                => [
                (new In('id', (new Select('u'))->columns(['id'])->where(['active' => 1])))
                    ->setSpecification('%1$s = ANY %2$s'),
                'SELECT "t".* FROM "t" WHERE "id" = ANY (SELECT "u"."id" AS "id" FROM "u" WHERE "active" = \'1\')',
            ],
        ];
    }

    #[Test]
    public function canPassIdentifierAndEmptyValueSetToConstructor(): void
    {
        $in = new In('foo.bar', []);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify empty value set was set correctly
        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet);
        static::assertEquals([], $valueSet->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    #[Test]
    public function canPassIdentifierAndValueSetToConstructor(): void
    {
        $in = new In('foo.bar', [1, 2]);

        // Verify identifier was set correctly
        $identifier = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        // Verify value set was set correctly
        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet);
        static::assertEquals([1, 2], $valueSet->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet->getType());
    }

    /** @param array<string, mixed> $expectedParameters */
    #[Test]
    #[DataProvider('customSpecificationPreparedProvider')]
    public function customSpecificationBindsEveryListValueOnce(
        In $in,
        string $expectedSql,
        array $expectedParameters,
    ): void {
        $statement = $this->prepare((new Select('t'))->where([$in]));

        static::assertSame(
            [$expectedSql, $expectedParameters],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    #[Test]
    public function customSpecificationBindsSubSelectParametersOnce(): void
    {
        $in = (new In('id', (new Select('u'))->columns(['id'])->where(['active' => 1])))
            ->setSpecification('%s = ANY %s');
        $statement = $this->prepare((new Select('t'))->where([$in]));

        static::assertSame(
            [
                'SELECT "t".* FROM "t" WHERE "id" = ANY (SELECT "u"."id" AS "id" FROM "u" WHERE "active" = :subselect1where1)',
                ['subselect1where1' => 1],
            ],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    #[Test]
    #[DataProvider('customSpecificationSqlProvider')]
    public function customSpecificationRendersEveryListValue(In $in, string $expectedSql): void
    {
        $select = (new Select('t'))->where([$in]);

        static::assertSame($expectedSql, $select->getSqlString(new TrustingSql92Platform()));
    }

    #[Test]
    public function emptyConstructorYieldsNullIdentifierAndValueSet(): void
    {
        $in = new In();
        static::assertNull($in->getIdentifier());
        static::assertNull($in->getValueSet());
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $in = new In();
        $in->setValueSet([1, 2]);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_IDENTIFIER);
        $in->getExpressionData();
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenValueSetNotSet(): void
    {
        $in = new In();
        $in->setIdentifier('foo');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_VALUE_SET);
        $in->getExpressionData();
    }

    #[Test]
    public function getExpressionDataWithEmptyValues(): void
    {
        new Select();
        $in = new In('foo', []);

        $expressionData = $in->getExpressionData();

        static::assertSame('%s IN (NULL)', $expressionData['spec']);
    }

    #[Test]
    public function getExpressionDataWithSubselect(): void
    {
        $select = new Select();
        $in     = new In(Argument::value('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify value argument (passed as value type)
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function getExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select = new Select();
        $in     = new In(Argument::identifiers(['foo', 'bar']), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('(%s, %s) IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify array identifiers argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertEquals(['foo', 'bar'], $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifiers, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function getExpressionDataWithSubselectAndIdentifier(): void
    {
        $select = new Select();
        $in     = new In(Argument::identifier('foo'), $select);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify subselect argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame($select, $values[1]->getValue());
        static::assertEquals(ArgumentType::Select, $values[1]->getType());
    }

    #[Test]
    public function identifierIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setIdentifier('foo.bar');

        // Verify fluent interface
        static::assertSame($in, $result);

        // Verify the first mutation occurred
        $identifier1 = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('foo.bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $in->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $in->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz.qux', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN (%s, %s, %s)', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertEquals([1, 2, 3], $values[1]->getValue());
        static::assertEquals(ArgumentType::Values, $values[1]->getType());

        // Test with typed value sets
        $in->setIdentifier('foo.bar')
            ->setValueSet([
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ]);

        $expressionData = $in->getExpressionData();

        // Verify specification
        static::assertSame('%s IN (%s, %s, %s)', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify value set argument with types
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertEquals(
            [
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ],
            $values[1]->getValue(),
        );
        static::assertEquals(ArgumentType::Values, $values[1]->getType());
    }

    #[Test]
    public function setValueSetWithArgumentInterfacePassesThrough(): void
    {
        $in     = new In();
        $values = new Values([1, 2, 3]);

        $in->setValueSet($values);

        static::assertSame($values, $in->getValueSet());
    }

    #[Test]
    public function setValueSetWithSelectWrapsInArgumentSelect(): void
    {
        $in     = new In();
        $select = new Select('users');

        $in->setValueSet($select);

        $valueSet = $in->getValueSet();
        static::assertInstanceOf(ArgumentSelect::class, $valueSet);
        static::assertSame(ArgumentType::Select, $valueSet->getType());
    }

    #[Test]
    public function valueSetIsMutable(): void
    {
        $in = new In();

        // First mutation
        $result = $in->setValueSet([1, 2]);

        // Verify fluent interface
        static::assertSame($in, $result);

        // Verify the first mutation occurred
        $valueSet1 = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet1);
        static::assertEquals([1, 2], $valueSet1->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet1->getType());

        // Second mutation with different data to verify mutability
        $in->setValueSet([3, 4, 5]);

        // Verify the instance was actually mutated
        $valueSet2 = $in->getValueSet();
        static::assertInstanceOf(ArgumentInterface::class, $valueSet2);
        static::assertEquals([3, 4, 5], $valueSet2->getValue());
        static::assertEquals(ArgumentType::Values, $valueSet2->getType());
    }

    private function prepare(Select $select): StatementContainer
    {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());

        $statement = new StatementContainer();
        $select->prepareStatement($adapter, $statement);

        return $statement;
    }
}
