<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Argument\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Having;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\Literal;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;
use TypeError;

#[CoversMethod(Select::class, '__construct')]
#[CoversMethod(Select::class, 'from')]
#[CoversMethod(Select::class, 'quantifier')]
#[CoversMethod(Select::class, 'columns')]
#[CoversMethod(Select::class, 'join')]
#[CoversMethod(Select::class, 'where')]
#[CoversMethod(Select::class, 'group')]
#[CoversMethod(Select::class, 'having')]
#[CoversMethod(Select::class, 'order')]
#[CoversMethod(Select::class, 'limit')]
#[CoversMethod(Select::class, 'offset')]
#[CoversMethod(Select::class, 'combine')]
#[CoversMethod(Select::class, 'reset')]
#[CoversMethod(Select::class, 'setSpecification')]
#[CoversMethod(Select::class, 'getRawState')]
#[CoversMethod(Select::class, 'isTableReadOnly')]
#[CoversMethod(Select::class, 'prepareStatement')]
#[CoversMethod(Select::class, 'getSqlString')]
#[CoversMethod(Select::class, '__get')]
#[CoversMethod(Select::class, '__clone')]
#[CoversMethod(Select::class, 'processJoins')]
#[CoversMethod(Select::class, 'processSelect')]
#[CoversMethod(Select::class, 'processWhere')]
#[CoversMethod(Select::class, 'processGroup')]
#[CoversMethod(Select::class, 'processHaving')]
#[CoversMethod(Select::class, 'processOrder')]
#[CoversMethod(Select::class, 'processLimit')]
#[CoversMethod(Select::class, 'processOffset')]
#[CoversMethod(Select::class, 'processCombine')]
final class SelectTest extends TestCase
{
    use AdapterTestTrait;

    public function testConstruct(): void
    {
        $select = new Select('foo');
        self::assertEquals('foo', $select->getRawState('table'));
    }

    #[TestDox('unit test: Test from() returns Select object (is chainable)')]
    public function testFrom(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->from('foo');

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        self::assertEquals('foo', $select->getRawState('table'));

        // Second mutation to verify mutability
        $select->from('bar');

        // Verify the instance was actually mutated
        self::assertEquals('bar', $select->getRawState('table'));
    }

    #[TestDox('unit test: Test quantifier() returns Select object (is chainable)')]
    public function testQuantifier(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->quantifier(Select::QUANTIFIER_DISTINCT);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        self::assertEquals(Select::QUANTIFIER_DISTINCT, $select->getRawState('quantifier'));

        // Second mutation to verify mutability
        $select->quantifier(Select::QUANTIFIER_ALL);

        // Verify the instance was actually mutated
        self::assertEquals(Select::QUANTIFIER_ALL, $select->getRawState('quantifier'));
    }

    #[TestDox('unit test: Test quantifier() accepts expression')]
    public function testQuantifierParameterExpressionInterface(): void
    {
        $expr   = $this->getMockBuilder(ExpressionInterface::class)->onlyMethods([])->getMock();
        $select = new Select();
        /** @psalm-suppress InvalidArgument */
        $select->quantifier($expr);
        self::assertSame(
            $expr,
            $select->getRawState(Select::QUANTIFIER)
        );
    }

    #[TestDox('unit test: Test columns() returns Select object (is chainable)')]
    public function testColumns(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->columns(['foo', 'bar']);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        self::assertEquals(['foo', 'bar'], $select->getRawState('columns'));

        // Second mutation to verify mutability
        $select->columns(['baz', 'qux']);

        // Verify the instance was actually mutated
        self::assertEquals(['baz', 'qux'], $select->getRawState('columns'));
    }

    #[TestDox('unit test: Test isTableReadOnly() returns correct state for read only')]
    public function testIsTableReadOnly(): void
    {
        $select = new Select('foo');
        self::assertTrue($select->isTableReadOnly());

        $select = new Select();
        self::assertFalse($select->isTableReadOnly());
    }

    #[TestDox('unit test: Test join() returns same Select object (is chainable)')]
    public function testJoin(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->join('foo', 'x = y');

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        $joins = $select->getRawState('joins');
        self::assertInstanceOf(Join::class, $joins);
        self::assertEquals(
            [
                [
                    'name'    => 'foo',
                    'on'      => 'x = y',
                    'columns' => [Select::SQL_STAR],
                    'type'    => Select::JOIN_INNER,
                ],
            ],
            $joins->getJoins()
        );

        // Second mutation to verify mutability (joins accumulate)
        $select->join('bar', 'a = b');

        // Verify the instance was actually mutated
        $joins2 = $select->getRawState('joins');
        self::assertCount(2, $joins2->getJoins());
        self::assertEquals('bar', $joins2->getJoins()[1]['name']);
    }

    #[TestDox('unit test: Test join() exception with bad join')]
    public function testBadJoin(): void
    {
        $select = new Select();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("expects 'foo' as");
        $select->join(['foo'], 'x = y');
    }

    /**
     * @throws ReflectionException
     */
    #[TestDox('unit test: Test processJoins() exception with bad join name')]
    public function testBadJoinName(): void
    {
        $mockExpression = $this->getMockBuilder(ExpressionInterface::class)
            ->getMock();
        $mockDriver     = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $parameterContainer = new ParameterContainer();

        $select = new Select();
        $select->join(['foo' => $mockExpression], 'x = y');

        $sr = new ReflectionObject($select);

        $mr = $sr->getMethod('processJoins');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $mr->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $mr->invokeArgs($select, [new Sql92(), $mockDriver, $parameterContainer]);
    }

    #[TestDox('unit test: Test where() returns Select object (is chainable)')]
    public function testWhereReturnsSameSelectObject(): void
    {
        $select = new Select();
        self::assertSame($select, $select->where('x = y'));
    }

    #[TestDox('unit test: Test where() will accept a string for the predicate to create an expression predicate')]
    public function testWhereArgument1IsString(): void
    {
        $select = new Select();
        $select->where('x = ?');

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertCount(1, $predicates);
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Predicate\Expression::class, $predicates[0][1]);
        self::assertEquals(Predicate\PredicateSet::OP_AND, $predicates[0][0]);
        self::assertEquals('x = ?', $predicates[0][1]->getExpression());

        $select = new Select();
        $select->where('x = y');

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);
    }

    #[TestDox('unit test: Test where() will accept an array with a string key (containing ?) used as an
                    expression with placeholder')]
    public function testWhereArgument1IsAssociativeArrayContainingReplacementCharacter(): void
    {
        $select = new Select();
        $select->where(['foo > ?' => 5]);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $expression = Argument::value(5);

        self::assertCount(1, $predicates);
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Predicate\Expression::class, $predicates[0][1]);
        self::assertEquals(Predicate\PredicateSet::OP_AND, $predicates[0][0]);
        self::assertEquals('foo > ?', $predicates[0][1]->getExpression());
        self::assertEquals([$expression], $predicates[0][1]->getParameters());
    }

    #[TestDox('unit test: Test where() will accept any array with string key (without ?) to be used
                    as Operator predicate')]
    public function testWhereArgument1IsAssociativeArrayNotContainingReplacementCharacter(): void
    {
        $select = new Select();
        $select->where(['name' => 'Ralph', 'age' => 33]);

        $identifier1 = Argument::identifier('name');
        $expression1 = Argument::value('Ralph');
        $identifier2 = Argument::identifier('age');
        $expression2 = Argument::value(33);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertCount(2, $predicates);
        self::assertIsArray($predicates[0]);
        self::assertIsArray($predicates[1]);

        self::assertInstanceOf(Operator::class, $predicates[0][1]);
        self::assertEquals(Predicate\PredicateSet::OP_AND, $predicates[0][0]);
        self::assertEquals($identifier1, $predicates[0][1]->getLeft());
        self::assertEquals($expression1, $predicates[0][1]->getRight());

        self::assertInstanceOf(Operator::class, $predicates[1][1]);
        self::assertEquals(Predicate\PredicateSet::OP_AND, $predicates[1][0]);
        self::assertEquals($identifier2, $predicates[1][1]->getLeft());
        self::assertEquals($expression2, $predicates[1][1]->getRight());

        $select = new Select();
        $select->where(['x = y']);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);
    }

    #[TestDox('
        unit test: Test where() will accept any array with string key (without ?) with Predicate throw Exception
    ')]
    public function testWhereArgument1IsAssociativeArrayIsPredicate(): void
    {
        $select = new Select();
        $where  = [
            'name' => new Predicate\Literal("name = 'Ralph'"),
            'age'  => new Predicate\Expression('age = ?', 33),
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using Predicate must not use string keys');
        $select->where($where);
    }

    #[TestDox('unit test: Test where() will accept an indexed array to be used by joining string expressions')]
    public function testWhereArgument1IsIndexedArray(): void
    {
        $select = new Select();
        $select->where(['name = "Ralph"']);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertCount(1, $predicates);
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);
        self::assertEquals(Predicate\PredicateSet::OP_AND, $predicates[0][0]);
        self::assertEquals('name = "Ralph"', $predicates[0][1]->getLiteral());
    }

    #[TestDox('unit test: Test where() will accept an indexed array to be used by joining string expressions,
                    combined by OR')]
    public function testWhereArgument1IsIndexedArrayArgument2IsOr(): void
    {
        $select = new Select();
        $select->where(['name = "Ralph"'], Predicate\PredicateSet::OP_OR);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertCount(1, $predicates);
        self::assertIsArray($predicates[0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);
        self::assertEquals(Predicate\PredicateSet::OP_OR, $predicates[0][0]);
        self::assertEquals('name = "Ralph"', $predicates[0][1]->getLiteral());
    }

    #[TestDox('unit test: Test where() will accept a closure to be executed with Where object as argument')]
    public function testWhereArgument1IsClosure(): void
    {
        $select = new Select();
        /** @var Where $where */
        $where = $select->getRawState('where');

        $select->where(function (Where $what) use ($where): void {
            self::assertSame($where, $what);
        });
    }

    #[TestDox('unit test: Test where() will accept any Predicate object as-is')]
    public function testWhereArgument1IsPredicate(): void
    {
        $select    = new Select();
        $predicate = new Predicate\Predicate([
            new Predicate\Expression('name = ?', 'Ralph'),
            new Predicate\Expression('age = ?', 33),
        ]);
        $select->where($predicate);

        /** @var Where $where */
        $where      = $select->getRawState('where');
        $predicates = $where->getPredicates();
        self::assertIsArray($predicates[0]);
        self::assertSame($predicate, $predicates[0][1]);
    }

    #[TestDox('unit test: Test where() will accept a Where object')]
    public function testWhereArgument1IsWhereObject(): void
    {
        $select = new Select();
        $select->where($newWhere = new Where());
        self::assertSame($newWhere, $select->getRawState('where'));
    }

    /**
     * @throws ReflectionException
     */
    #[TestDox('unit test: Test order()')]
    public function testOrder(): void
    {
        $select = new Select();
        $return = $select->order('id DESC');
        self::assertSame($select, $return); // test fluent interface
        self::assertEquals(['id DESC'], $select->getRawState('order'));

        $select = new Select();
        $select->order('id DESC')
            ->order('name ASC, age DESC');
        self::assertEquals(['id DESC', 'name ASC', 'age DESC'], $select->getRawState('order'));

        $select = new Select();
        $select->order(['name ASC', 'age DESC']);
        self::assertEquals(['name ASC', 'age DESC'], $select->getRawState('order'));

        $select = new Select();
        $select->order(new Expression('RAND()'));

        $sr     = new ReflectionObject($select);
        $method = $sr->getMethod('processOrder');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        self::assertEquals(
            [[['RAND()']]],
            $method->invokeArgs($select, [new TrustingSql92Platform()])
        );

        $select = new Select();
        /** @psalm-suppress InvalidArgument - mocked Operator */
        $select->order(
            $this->getMockBuilder(Operator::class)
                ->onlyMethods([])
                ->setConstructorArgs(['rating', '<', '10'])
                ->getMock()
        );
        $sr     = new ReflectionObject($select);
        $method = $sr->getMethod('processOrder');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        self::assertEquals(
            [[['"rating" < \'10\'']]],
            $method->invokeArgs($select, [new TrustingSql92Platform()])
        );
    }

    #[TestDox('unit test: Test order() correctly splits parameters.')]
    public function testOrderCorrectlySplitsParameter(): void
    {
        $select = new Select();
        $select->order('name  desc');
        self::assertEquals(
            'SELECT * ORDER BY "name" DESC',
            $select->getSqlString(new TrustingSql92Platform())
        );
    }

    #[TestDox(': unit test: test limit()')]
    public function testLimit(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->limit(5);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        $limit = $select->getRawState(Select::LIMIT);
        self::assertIsNumeric($limit);
        self::assertEquals(5, $limit);

        // Second mutation to verify mutability
        $select->limit(10);

        // Verify the instance was actually mutated
        self::assertEquals(10, $select->getRawState(Select::LIMIT));
    }

    #[TestDox(': unit test: test limit() throws exception when invalid parameter passed')]
    public function testLimitExceptionOnInvalidParameter(): void
    {
        $select = new Select();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Select::class . '::limit expects parameter to be numeric');
        $select->limit('foobar');
    }

    #[TestDox(': unit test: test offset()')]
    public function testOffset(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->offset(10);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        $offset = $select->getRawState(Select::OFFSET);
        self::assertIsNumeric($offset);
        self::assertEquals(10, $offset);

        // Second mutation to verify mutability
        $select->offset(20);

        // Verify the instance was actually mutated
        self::assertEquals(20, $select->getRawState(Select::OFFSET));
    }

    #[TestDox(': unit test: test offset() throws exception when invalid parameter passed')]
    public function testOffsetExceptionOnInvalidParameter(): void
    {
        $select = new Select();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(Select::class . '::offset expects parameter to be numeric');
        $select->offset('foobar');
    }

    #[TestDox('unit test: Test group() returns same Select object (is chainable)')]
    public function testGroup(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->group(['col1', 'col2']);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        self::assertEquals(['col1', 'col2'], $select->getRawState('group'));

        // Second mutation to verify mutability (group accumulates)
        $select->group('col3');

        // Verify the instance was actually mutated
        self::assertEquals(['col1', 'col2', 'col3'], $select->getRawState('group'));
    }

    #[TestDox('unit test: Test having() returns same Select object (is chainable)')]
    public function testHaving(): void
    {
        $select = new Select();

        // First mutation
        $result = $select->having(['x = ?' => 5]);

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        $having = $select->getRawState('having');
        self::assertInstanceOf(Having::class, $having);
        self::assertEquals(1, $having->count());

        // Second mutation to verify mutability (having predicates accumulate)
        $select->having(['y = ?' => 10]);

        // Verify the instance was actually mutated
        self::assertEquals(2, $select->getRawState('having')->count());
    }

    #[TestDox('unit test: Test having() returns same Select object (is chainable)')]
    public function testHavingArgument1IsHavingObject(): void
    {
        $select = new Select();
        $having = new Having();
        $return = $select->having($having);
        self::assertSame($select, $return);
        self::assertSame($having, $select->getRawState('having'));
    }

    #[TestDox('unit test: Test combine() returns same Select object (is chainable)')]
    public function testCombine(): void
    {
        $select  = new Select();
        $combine = new Select();

        // First mutation
        $result = $select->combine($combine, Select::COMBINE_UNION, 'ALL');

        // Verify fluent interface
        self::assertSame($select, $result);

        // Verify the first mutation occurred
        $state = $select->getRawState('combine');
        self::assertInstanceOf(Select::class, $state['select']);
        self::assertNotSame($select, $state['select']);
        self::assertEquals(Select::COMBINE_UNION, $state['type']);
        self::assertEquals('ALL', $state['modifier']);

        // Second mutation to verify mutability using a fresh Select
        $select2  = new Select();
        $combine2 = new Select();
        $select2->combine($combine2, Select::COMBINE_INTERSECT, 'DISTINCT');

        // Verify the instance was actually mutated
        $state2 = $select2->getRawState('combine');
        self::assertEquals(Select::COMBINE_INTERSECT, $state2['type']);
        self::assertEquals('DISTINCT', $state2['modifier']);
    }

    #[TestDox('unit test: Test reset() resets internal stat of Select object, based on input')]
    public function testReset(): void
    {
        $select = new Select();

        // table
        $select->from('foo');
        self::assertEquals('foo', $select->getRawState(Select::TABLE));
        $select->reset(Select::TABLE);
        self::assertNull($select->getRawState(Select::TABLE));

        // columns
        $select->columns(['foo']);
        self::assertEquals(['foo'], $select->getRawState(Select::COLUMNS));
        $select->reset(Select::COLUMNS);
        self::assertEmpty($select->getRawState(Select::COLUMNS));

        // joins
        $select->join('foo', 'id = boo');
        $joins = $select->getRawState(Select::JOINS);
        self::assertInstanceOf(Join::class, $joins);
        self::assertEquals(
            [['name' => 'foo', 'on' => 'id = boo', 'columns' => ['*'], 'type' => 'inner']],
            $joins->getJoins()
        );
        $select->reset(Select::JOINS);
        $emptyJoins = $select->getRawState(Select::JOINS);
        self::assertInstanceOf(Join::class, $emptyJoins);
        self::assertEmpty($emptyJoins->getJoins());

        // where
        $select->where('foo = bar');
        /** @var Where $where1 */
        $where1 = $select->getRawState(Select::WHERE);
        self::assertEquals(1, $where1->count());
        $select->reset(Select::WHERE);
        /** @var Where $where2 */
        $where2 = $select->getRawState(Select::WHERE);
        self::assertEquals(0, $where2->count());
        self::assertNotSame($where1, $where2);

        // group
        $select->group(['foo']);
        self::assertEquals(['foo'], $select->getRawState(Select::GROUP));
        $select->reset(Select::GROUP);
        self::assertEmpty($select->getRawState(Select::GROUP));

        // having
        $select->having('foo = bar');
        /** @var Having $having1 */
        $having1 = $select->getRawState(Select::HAVING);
        self::assertEquals(1, $having1->count());
        $select->reset(Select::HAVING);
        /** @var Having $having2 */
        $having2 = $select->getRawState(Select::HAVING);
        self::assertEquals(0, $having2->count());
        self::assertNotSame($having1, $having2);

        // limit
        $select->limit(5);
        self::assertEquals(5, $select->getRawState(Select::LIMIT));
        $select->reset(Select::LIMIT);
        self::assertNull($select->getRawState(Select::LIMIT));

        // offset
        $select->offset(10);
        self::assertEquals(10, $select->getRawState(Select::OFFSET));
        $select->reset(Select::OFFSET);
        self::assertNull($select->getRawState(Select::OFFSET));

        // order
        $select->order('foo asc');
        self::assertEquals(['foo asc'], $select->getRawState(Select::ORDER));
        $select->reset(Select::ORDER);
        self::assertEmpty($select->getRawState(Select::ORDER));
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[DataProvider('providerData')]
    #[TestDox('unit test: Test prepareStatement() will produce expected sql and parameters based on
                    a variety of provided arguments [uses data provider]')]
    public function testPrepareStatement(
        Select $select,
        string $expectedSqlString,
        array $expectedParameters,
        mixed $unused1,
        mixed $unused2,
        bool $useNamedParameters = false
    ): void {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver
            ->expects($this->any())
            ->method('formatParameterName')
            ->willReturnCallback(fn(string $name): string => $useNamedParameters ? ':' . $name : '?');

        $mockAdapter = $this->createMockAdapter($mockDriver);

        $parameterContainer = new ParameterContainer();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('getParameterContainer')
            ->willReturn($parameterContainer);
        $mockStatement->expects($this->any())->method('setSql')->with($this->equalTo($expectedSqlString));

        $select->prepareStatement($mockAdapter, $mockStatement);

        if ($expectedParameters !== []) {
            self::assertEquals($expectedParameters, $parameterContainer->getNamedArray());
        }
    }

    #[Group('Laminas-5192')]
    public function testSelectUsingTableIdentifierWithEmptyScheme(): void
    {
        $select = new Select();
        $select->from(new TableIdentifier('foo'));
        $select->join(new TableIdentifier('bar'), 'foo.id = bar.fooid');

        self::assertEquals(
            'SELECT "foo".*, "bar".* FROM "foo" INNER JOIN "bar" ON "foo"."id" = "bar"."fooid"',
            $select->getSqlString(new TrustingSql92Platform())
        );
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[DataProvider('providerData')]
    #[TestDox('unit test: Test getSqlString() will produce expected sql and parameters based on
                    a variety of provided arguments [uses data provider]')]
    public function testGetSqlString(Select $select, mixed $unused, mixed $unused2, string $expectedSqlString): void
    {
        self::assertEquals($expectedSqlString, $select->getSqlString(new TrustingSql92Platform()));
    }

    #[TestDox('unit test: Test __get() returns expected objects magically')]
    public function testMagicAccessor(): void
    {
        $select = new Select();
        self::assertInstanceOf(Where::class, $select->where);
    }

    #[TestDox('unit test: Test __clone() will clone the where object so that this select can be used
                    in multiple contexts')]
    public function testCloning(): void
    {
        $select  = new Select();
        $select1 = clone $select;
        $select1->where('id = foo');
        $select1->having('id = foo');

        self::assertEquals(0, $select->where->count());
        self::assertEquals(1, $select1->where->count());

        self::assertEquals(0, $select->having->count());
        self::assertEquals(1, $select1->having->count());
    }

    /**
     * @throws ReflectionException
     * @noinspection PhpUnusedParameterInspection
     */
    #[DataProvider('providerData')]
    #[TestDox('unit test: Text process*() methods will return proper array when internally called,
                    part of extension API')]
    public function testProcessMethods(
        Select $select,
        mixed $unused,
        mixed $unused2,
        mixed $unused3,
        array $internalTests
    ): void {
        if ($internalTests === []) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $parameterContainer = new ParameterContainer();

        $sr = new ReflectionObject($select);

        /**
         * @var string $method
         * @var array $expected
         */
        foreach ($internalTests as $method => $expected) {
            $mr = $sr->getMethod($method);
            /** @noinspection PhpExpressionResultUnusedInspection */
            $mr->setAccessible(true);
            /** @psalm-suppress MixedAssignment */
            $return = $mr->invokeArgs($select, [new Sql92(), $mockDriver, $parameterContainer]);
            self::assertEquals($expected, $return);
        }
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: Select,
     *     1: string,
     *     2: array<string, mixed>,
     *     3: string,
     *     4: array,
     *     5: bool,
     * }>
     */
    public static function providerData(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        // basic table
        $select0 = new Select();
        $select0->from('foo');

        $sqlPrep0       = 'SELECT "foo".* FROM "foo"';
        $sqlStr0        = 'SELECT "foo".* FROM "foo"';
        $internalTests0 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
        ];

        // table as TableIdentifier
        $select1 = new Select();
        $select1->from(new TableIdentifier('foo', 'bar'));

        $sqlPrep1       = 'SELECT "bar"."foo".* FROM "bar"."foo"';
        $sqlStr1        = 'SELECT "bar"."foo".* FROM "bar"."foo"';
        $internalTests1 = [
            'processSelect' => [[['"bar"."foo".*']], '"bar"."foo"'],
        ];

        // table with alias
        $select2 = new Select();
        $select2->from(['f' => 'foo']);

        $sqlPrep2       = 'SELECT "f".* FROM "foo" AS "f"';
        $sqlStr2        = 'SELECT "f".* FROM "foo" AS "f"';
        $internalTests2 = [
            'processSelect' => [[['"f".*']], '"foo" AS "f"'],
        ];

        // table with alias with table as TableIdentifier
        $select3 = new Select();
        $select3->from(['f' => new TableIdentifier('foo')]);

        $sqlPrep3       = 'SELECT "f".* FROM "foo" AS "f"';
        $sqlStr3        = 'SELECT "f".* FROM "foo" AS "f"';
        $internalTests3 = [
            'processSelect' => [[['"f".*']], '"foo" AS "f"'],
        ];

        // columns
        $select4 = new Select();
        $select4->from('foo')->columns(['bar', 'baz']);
        $sqlPrep4       = 'SELECT "foo"."bar" AS "bar", "foo"."baz" AS "baz" FROM "foo"';
        $sqlStr4        = 'SELECT "foo"."bar" AS "bar", "foo"."baz" AS "baz" FROM "foo"';
        $internalTests4 = [
            'processSelect' => [[['"foo"."bar"', '"bar"'], ['"foo"."baz"', '"baz"']], '"foo"'],
        ];

        // columns with AS associative array
        $select5 = new Select();
        $select5->from('foo')->columns(['bar' => 'baz']);
        $sqlPrep5       = 'SELECT "foo"."baz" AS "bar" FROM "foo"';
        $sqlStr5        = 'SELECT "foo"."baz" AS "bar" FROM "foo"';
        $internalTests5 = [
            'processSelect' => [[['"foo"."baz"', '"bar"']], '"foo"'],
        ];

        // columns with AS associative array mixed
        $select6 = new Select();
        $select6->from('foo')->columns(['bar' => 'baz', 'bam']);
        $sqlPrep6       = 'SELECT "foo"."baz" AS "bar", "foo"."bam" AS "bam" FROM "foo"';
        $sqlStr6        = 'SELECT "foo"."baz" AS "bar", "foo"."bam" AS "bam" FROM "foo"';
        $internalTests6 = [
            'processSelect' => [[['"foo"."baz"', '"bar"'], ['"foo"."bam"', '"bam"']], '"foo"'],
        ];

        // columns where value is Expression, with AS
        $select7 = new Select();
        $select7->from('foo')->columns(['bar' => new Expression('COUNT(some_column)')]);
        $sqlPrep7       = 'SELECT COUNT(some_column) AS "bar" FROM "foo"';
        $sqlStr7        = 'SELECT COUNT(some_column) AS "bar" FROM "foo"';
        $internalTests7 = [
            'processSelect' => [[['COUNT(some_column)', '"bar"']], '"foo"'],
        ];

        // columns where value is Expression
        $select8 = new Select();
        $select8->from('foo')->columns([new Expression('COUNT(some_column) AS bar')]);
        $sqlPrep8       = 'SELECT COUNT(some_column) AS bar FROM "foo"';
        $sqlStr8        = 'SELECT COUNT(some_column) AS bar FROM "foo"';
        $internalTests8 = [
            'processSelect' => [[['COUNT(some_column) AS bar']], '"foo"'],
        ];

        // columns where value is Expression with parameters
        $select9 = new Select();
        $select9->from('foo')->columns(
            [
                new Expression(
                    '(COUNT(?) + ?) AS ?',
                    [
                        ['some_column' => ArgumentType::Identifier],
                        [5 => ArgumentType::Value],
                        ['bar' => ArgumentType::Identifier],
                    ],
                ),
            ]
        );
        $sqlPrep9       = 'SELECT (COUNT("some_column") + ?) AS "bar" FROM "foo"';
        $sqlStr9        = 'SELECT (COUNT("some_column") + \'5\') AS "bar" FROM "foo"';
        $params9        = ['column1' => 5];
        $internalTests9 = [
            'processSelect' => [[['(COUNT("some_column") + ?) AS "bar"']], '"foo"'],
        ];

        // joins (plain)
        $select10 = new Select();
        $select10->from('foo')->join('zac', 'm = n');
        $sqlPrep10       = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $sqlStr10        = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $internalTests10 = [
            'processSelect' => [[['"foo".*'], ['"zac".*']], '"foo"'],
            'processJoins'  => [[['INNER', '"zac"', '"m" = "n"']]],
        ];

        // join with columns
        $select11 = new Select();
        $select11->from('foo')->join('zac', 'm = n', ['bar', 'baz']);
        $sqlPrep11       = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $sqlStr11        = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $internalTests11 = [
            'processSelect' => [[['"foo".*'], ['"zac"."bar"', '"bar"'], ['"zac"."baz"', '"baz"']], '"foo"'],
            'processJoins'  => [[['INNER', '"zac"', '"m" = "n"']]],
        ];

        // join with alternate type
        $select12 = new Select();
        $select12->from('foo')->join('zac', 'm = n', ['bar', 'baz'], Select::JOIN_OUTER);
        $sqlPrep12       = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" OUTER JOIN "zac" ON "m" = "n"';
        $sqlStr12        = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" OUTER JOIN "zac" ON "m" = "n"';
        $internalTests12 = [
            'processSelect' => [[['"foo".*'], ['"zac"."bar"', '"bar"'], ['"zac"."baz"', '"baz"']], '"foo"'],
            'processJoins'  => [[['OUTER', '"zac"', '"m" = "n"']]],
        ];

        // join with column aliases
        $select13 = new Select();
        $select13->from('foo')->join('zac', 'm = n', ['BAR' => 'bar', 'BAZ' => 'baz']);
        $sqlPrep13       = 'SELECT "foo".*, "zac"."bar" AS "BAR", "zac"."baz" AS "BAZ" FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $sqlStr13        = 'SELECT "foo".*, "zac"."bar" AS "BAR", "zac"."baz" AS "BAZ" FROM "foo" INNER JOIN "zac" ON "m" = "n"';
        $internalTests13 = [
            'processSelect' => [[['"foo".*'], ['"zac"."bar"', '"BAR"'], ['"zac"."baz"', '"BAZ"']], '"foo"'],
            'processJoins'  => [[['INNER', '"zac"', '"m" = "n"']]],
        ];

        // join with table aliases
        $select14 = new Select();
        $select14->from('foo')->join(['b' => 'bar'], 'b.foo_id = foo.foo_id');
        $sqlPrep14       = 'SELECT "foo".*, "b".* FROM "foo" INNER JOIN "bar" AS "b" ON "b"."foo_id" = "foo"."foo_id"';
        $sqlStr14        = 'SELECT "foo".*, "b".* FROM "foo" INNER JOIN "bar" AS "b" ON "b"."foo_id" = "foo"."foo_id"';
        $internalTests14 = [
            'processSelect' => [[['"foo".*'], ['"b".*']], '"foo"'],
            'processJoins'  => [[['INNER', '"bar" AS "b"', '"b"."foo_id" = "foo"."foo_id"']]],
        ];

        // where (simple string)
        $select15 = new Select();
        $select15->from('foo')->where('x = 5');
        $sqlPrep15       = 'SELECT "foo".* FROM "foo" WHERE x = 5';
        $sqlStr15        = 'SELECT "foo".* FROM "foo" WHERE x = 5';
        $internalTests15 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processWhere'  => ['x = 5'],
        ];

        // where (returning parameters)
        $select16 = new Select();
        $select16->from('foo')->where(['x = ?' => 5]);
        $sqlPrep16       = 'SELECT "foo".* FROM "foo" WHERE x = ?';
        $sqlStr16        = 'SELECT "foo".* FROM "foo" WHERE x = \'5\'';
        $params16        = ['where1' => 5];
        $internalTests16 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processWhere'  => ['x = ?'],
        ];

        // group
        $select17 = new Select();
        $select17->from('foo')->group(['col1', 'col2']);
        $sqlPrep17       = 'SELECT "foo".* FROM "foo" GROUP BY "col1", "col2"';
        $sqlStr17        = 'SELECT "foo".* FROM "foo" GROUP BY "col1", "col2"';
        $internalTests17 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processGroup'  => [['"col1"', '"col2"']],
        ];

        $select18 = new Select();
        $select18->from('foo')->group('col1')->group('col2');
        $sqlPrep18       = 'SELECT "foo".* FROM "foo" GROUP BY "col1", "col2"';
        $sqlStr18        = 'SELECT "foo".* FROM "foo" GROUP BY "col1", "col2"';
        $internalTests18 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processGroup'  => [['"col1"', '"col2"']],
        ];

        $select19 = new Select();
        $select19->from('foo')->group(new Expression('DAY(?)', [['col1' => ArgumentType::Identifier]]));
        $sqlPrep19       = 'SELECT "foo".* FROM "foo" GROUP BY DAY("col1")';
        $sqlStr19        = 'SELECT "foo".* FROM "foo" GROUP BY DAY("col1")';
        $internalTests19 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processGroup'  => [['DAY("col1")']],
        ];

        // having (simple string)
        $select20 = new Select();
        $select20->from('foo')->having('x = 5');
        $sqlPrep20       = 'SELECT "foo".* FROM "foo" HAVING x = 5';
        $sqlStr20        = 'SELECT "foo".* FROM "foo" HAVING x = 5';
        $internalTests20 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processHaving' => ['x = 5'],
        ];

        // having (returning parameters)
        $select21 = new Select();
        $select21->from('foo')->having(['x = ?' => 5]);
        $sqlPrep21       = 'SELECT "foo".* FROM "foo" HAVING x = ?';
        $sqlStr21        = 'SELECT "foo".* FROM "foo" HAVING x = \'5\'';
        $params21        = ['having1' => 5];
        $internalTests21 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processHaving' => ['x = ?'],
        ];

        // order
        $select22 = new Select();
        $select22->from('foo')->order('c1');
        $sqlPrep22       = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC';
        $sqlStr22        = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC';
        $internalTests22 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processOrder'  => [[['"c1"', Select::ORDER_ASCENDING]]],
        ];

        $select23 = new Select();
        $select23->from('foo')->order(['c1', 'c2']);
        $sqlPrep23       = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC, "c2" ASC';
        $sqlStr23        = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC, "c2" ASC';
        $internalTests23 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processOrder'  => [[['"c1"', Select::ORDER_ASCENDING], ['"c2"', Select::ORDER_ASCENDING]]],
        ];

        $select24 = new Select();
        $select24->from('foo')->order(['c1' => 'DESC', 'c2' => 'Asc']);
        // notice partially lower case ASC
        $sqlPrep24       = 'SELECT "foo".* FROM "foo" ORDER BY "c1" DESC, "c2" ASC';
        $sqlStr24        = 'SELECT "foo".* FROM "foo" ORDER BY "c1" DESC, "c2" ASC';
        $internalTests24 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processOrder'  => [[['"c1"', Select::ORDER_DESCENDING], ['"c2"', Select::ORDER_ASCENDING]]],
        ];

        $select25 = new Select();
        $select25->from('foo')->order(['c1' => 'asc'])->order('c2 desc');
        // notice partially lower case ASC
        $sqlPrep25       = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC, "c2" DESC';
        $sqlStr25        = 'SELECT "foo".* FROM "foo" ORDER BY "c1" ASC, "c2" DESC';
        $internalTests25 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processOrder'  => [[['"c1"', Select::ORDER_ASCENDING], ['"c2"', Select::ORDER_DESCENDING]]],
        ];

        // limit
        $select26 = new Select();
        $select26->from('foo')->limit(5);
        $sqlPrep26       = 'SELECT "foo".* FROM "foo" LIMIT ?';
        $sqlStr26        = 'SELECT "foo".* FROM "foo" LIMIT \'5\'';
        $params26        = ['limit' => 5];
        $internalTests26 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processLimit'  => ['?'],
        ];

        // limit with offset
        $select27 = new Select();
        $select27->from('foo')->limit(5)->offset(10);
        $sqlPrep27       = 'SELECT "foo".* FROM "foo" LIMIT ? OFFSET ?';
        $sqlStr27        = 'SELECT "foo".* FROM "foo" LIMIT \'5\' OFFSET \'10\'';
        $params27        = ['limit' => 5, 'offset' => 10];
        $internalTests27 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processLimit'  => ['?'],
            'processOffset' => ['?'],
        ];

        // joins with a few keywords in the on clause
        $select28 = new Select();
        $select28->from('foo')->join('zac', '(m = n AND c.x) BETWEEN x AND y.z OR (c.x < y.z AND c.x <= y.z AND c.x > y.z AND c.x >= y.z)');
        $sqlPrep28       = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON ("m" = "n" AND "c"."x") BETWEEN "x" AND "y"."z" OR ("c"."x" < "y"."z" AND "c"."x" <= "y"."z" AND "c"."x" > "y"."z" AND "c"."x" >= "y"."z")';
        $sqlStr28        = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON ("m" = "n" AND "c"."x") BETWEEN "x" AND "y"."z" OR ("c"."x" < "y"."z" AND "c"."x" <= "y"."z" AND "c"."x" > "y"."z" AND "c"."x" >= "y"."z")';
        $internalTests28 = [
            'processSelect' => [[['"foo".*'], ['"zac".*']], '"foo"'],
            'processJoins'  => [[['INNER', '"zac"', '("m" = "n" AND "c"."x") BETWEEN "x" AND "y"."z" OR ("c"."x" < "y"."z" AND "c"."x" <= "y"."z" AND "c"."x" > "y"."z" AND "c"."x" >= "y"."z")']]],
        ];

        // order with compound name
        $select29 = new Select();
        $select29->from('foo')->order('c1.d2');
        $sqlPrep29       = 'SELECT "foo".* FROM "foo" ORDER BY "c1"."d2" ASC';
        $sqlStr29        = 'SELECT "foo".* FROM "foo" ORDER BY "c1"."d2" ASC';
        $internalTests29 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processOrder'  => [[['"c1"."d2"', Select::ORDER_ASCENDING]]],
        ];

        // group with compound name
        $select30 = new Select();
        $select30->from('foo')->group('c1.d2');
        $sqlPrep30       = 'SELECT "foo".* FROM "foo" GROUP BY "c1"."d2"';
        $sqlStr30        = 'SELECT "foo".* FROM "foo" GROUP BY "c1"."d2"';
        $internalTests30 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processGroup'  => [['"c1"."d2"']],
        ];

        // join with expression in ON part
        $select31 = new Select();
        $select31->from('foo')->join('zac', new Predicate\Expression('(m = n AND c.x) BETWEEN x AND y.z'));
        $sqlPrep31       = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON (m = n AND c.x) BETWEEN x AND y.z';
        $sqlStr31        = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON (m = n AND c.x) BETWEEN x AND y.z';
        $internalTests31 = [
            'processSelect' => [[['"foo".*'], ['"zac".*']], '"foo"'],
            'processJoins'  => [[['INNER', '"zac"', '(m = n AND c.x) BETWEEN x AND y.z']]],
        ];

        $select32subselect = new Select();
        $select32subselect->from('bar')->where->like('y', '%Foo%');
        $select32 = new Select();
        $select32->from(['x' => $select32subselect]);

        $sqlPrep32       = 'SELECT "x".* FROM (SELECT "bar".* FROM "bar" WHERE "y" LIKE ?) AS "x"';
        $sqlStr32        = 'SELECT "x".* FROM (SELECT "bar".* FROM "bar" WHERE "y" LIKE \'%Foo%\') AS "x"';
        $internalTests32 = [
            'processSelect' => [[['"x".*']], '(SELECT "bar".* FROM "bar" WHERE "y" LIKE ?) AS "x"'],
        ];

        $select33 = new Select();
        $select33->from('table')->columns(['*'])->where([
            'c1' => null,
            'c2' => [1, 2, 3],
            new IsNotNull('c3'),
        ]);
        $sqlPrep33       = 'SELECT "table".* FROM "table" WHERE "c1" IS NULL AND "c2" IN (?, ?, ?) AND "c3" IS NOT NULL';
        $sqlStr33        = 'SELECT "table".* FROM "table" WHERE "c1" IS NULL AND "c2" IN (\'1\', \'2\', \'3\') AND "c3" IS NOT NULL';
        $internalTests33 = [
            'processSelect' => [[['"table".*']], '"table"'],
            'processWhere'  => ['"c1" IS NULL AND "c2" IN (?, ?, ?) AND "c3" IS NOT NULL'],
        ];

        // @author Demian Katz
        $select34 = new Select();
        $select34->from('table')->order([
            new Expression('isnull(?) DESC', [['name' => ArgumentType::Identifier]]),
            'name',
        ]);
        $sqlPrep34       = 'SELECT "table".* FROM "table" ORDER BY isnull("name") DESC, "name" ASC';
        $sqlStr34        = 'SELECT "table".* FROM "table" ORDER BY isnull("name") DESC, "name" ASC';
        $internalTests34 = [
            'processOrder' => [[['isnull("name") DESC'], ['"name"', Select::ORDER_ASCENDING]]],
        ];

        // join with Expression object in COLUMNS part (Laminas-514)
        // @co-author Koen Pieters (kpieters)
        $select35 = new Select();
        $select35->from('foo')->columns([])->join('bar', 'm = n', ['thecount' => new Expression("COUNT(*)")]);
        $sqlPrep35       = 'SELECT COUNT(*) AS "thecount" FROM "foo" INNER JOIN "bar" ON "m" = "n"';
        $sqlStr35        = 'SELECT COUNT(*) AS "thecount" FROM "foo" INNER JOIN "bar" ON "m" = "n"';
        $internalTests35 = [
            'processSelect' => [[['COUNT(*)', '"thecount"']], '"foo"'],
            'processJoins'  => [[['INNER', '"bar"', '"m" = "n"']]],
        ];

        // multiple joins with expressions
        // reported by @jdolieslager
        $select36 = new Select();
        $select36->from('foo')
            ->join('tableA', new Predicate\Operator('id', '=', 1))
            ->join('tableB', new Predicate\Operator('id', '=', 2))
            ->join('tableC', new Predicate\PredicateSet([
                new Predicate\Operator('id', '=', 3),
                new Predicate\Operator('number', '>', 20),
            ]));
        $sqlPrep36       = 'SELECT "foo".*, "tableA".*, "tableB".*, "tableC".* FROM "foo"'
            . ' INNER JOIN "tableA" ON "id" = :join1part1 INNER JOIN "tableB" ON "id" = :join2part1 '
            . 'INNER JOIN "tableC" ON "id" = :join3part1 AND "number" > :join3part2';
        $sqlStr36        = 'SELECT "foo".*, "tableA".*, "tableB".*, "tableC".* FROM "foo" '
            . 'INNER JOIN "tableA" ON "id" = \'1\' INNER JOIN "tableB" ON "id" = \'2\' '
            . 'INNER JOIN "tableC" ON "id" = \'3\' AND "number" > \'20\'';
        $internalTests36 = [];

        /**
         * @link https://github.com/zendframework/zf2/pull/2714
         */
        $select37 = new Select();
        $select37->from('foo')->columns(['bar'], false);
        $sqlPrep37       = 'SELECT "bar" AS "bar" FROM "foo"';
        $sqlStr37        = 'SELECT "bar" AS "bar" FROM "foo"';
        $internalTests37 = [
            'processSelect' => [[['"bar"', '"bar"']], '"foo"'],
        ];

        // @link https://github.com/zendframework/zf2/issues/3294
        // Test TableIdentifier In Joins
        $select38 = new Select();
        $select38->from('foo')->columns([])
            ->join(new TableIdentifier('bar', 'baz'), 'm = n', ['thecount' => new Expression("COUNT(*)")]);
        $sqlPrep38       = 'SELECT COUNT(*) AS "thecount" FROM "foo" INNER JOIN "baz"."bar" ON "m" = "n"';
        $sqlStr38        = 'SELECT COUNT(*) AS "thecount" FROM "foo" INNER JOIN "baz"."bar" ON "m" = "n"';
        $internalTests38 = [
            'processSelect' => [[['COUNT(*)', '"thecount"']], '"foo"'],
            'processJoins'  => [[['INNER', '"baz"."bar"', '"m" = "n"']]],
        ];

        // subselect in join
        $select39subselect = new Select();
        $select39subselect->from('bar')->where->like('y', '%Foo%');
        $select39 = new Select();
        $select39->from('foo')->join(['z' => $select39subselect], 'z.foo = bar.id');
        $sqlPrep39       = 'SELECT "foo".*, "z".* FROM "foo" INNER JOIN (SELECT "bar".* FROM "bar" WHERE "y" LIKE ?) AS "z" ON "z"."foo" = "bar"."id"';
        $sqlStr39        = 'SELECT "foo".*, "z".* FROM "foo" INNER JOIN (SELECT "bar".* FROM "bar" WHERE "y" LIKE \'%Foo%\') AS "z" ON "z"."foo" = "bar"."id"';
        $internalTests39 = [
            'processJoins' => [
                [['INNER', '(SELECT "bar".* FROM "bar" WHERE "y" LIKE ?) AS "z"', '"z"."foo" = "bar"."id"']],
            ],
        ];

        // @link https://github.com/zendframework/zf2/issues/3294
        // Test TableIdentifier In Joins, with multiple joins
        $select40 = new Select();
        $select40->from('foo')
            ->join(['a' => new TableIdentifier('another_foo', 'another_schema')], 'a.x = foo.foo_column')
            ->join('bar', 'foo.colx = bar.colx');
        $sqlPrep40       = 'SELECT "foo".*, "a".*, "bar".* FROM "foo"'
        . ' INNER JOIN "another_schema"."another_foo" AS "a" ON "a"."x" = "foo"."foo_column"'
        . ' INNER JOIN "bar" ON "foo"."colx" = "bar"."colx"';
        $sqlStr40        = 'SELECT "foo".*, "a".*, "bar".* FROM "foo"'
        . ' INNER JOIN "another_schema"."another_foo" AS "a" ON "a"."x" = "foo"."foo_column"'
        . ' INNER JOIN "bar" ON "foo"."colx" = "bar"."colx"';
        $internalTests40 = [
            'processSelect' => [[['"foo".*'], ['"a".*'], ['"bar".*']], '"foo"'],
            'processJoins'  => [
                [
                    ['INNER', '"another_schema"."another_foo" AS "a"', '"a"."x" = "foo"."foo_column"'],
                    ['INNER', '"bar"', '"foo"."colx" = "bar"."colx"'],
                ],
            ],
        ];

        $select41 = new Select();
        $select41->from('foo')->quantifier(Select::QUANTIFIER_DISTINCT);
        $sqlPrep41       = 'SELECT DISTINCT "foo".* FROM "foo"';
        $sqlStr41        = 'SELECT DISTINCT "foo".* FROM "foo"';
        $internalTests41 = [
            'processSelect' => [Select::QUANTIFIER_DISTINCT, [['"foo".*']], '"foo"'],
        ];

        $select42 = new Select();
        $select42->from('foo')->quantifier(new Expression('TOP ?', [10]));
        $sqlPrep42       = 'SELECT TOP ? "foo".* FROM "foo"';
        $sqlStr42        = 'SELECT TOP \'10\' "foo".* FROM "foo"';
        $internalTests42 = [
            'processSelect' => ['TOP ?', [['"foo".*']], '"foo"'],
        ];

        $select43 = new Select();
        $select43->from(['x' => 'foo'])->columns(['bar' => 'foo.bar'], false);
        $sqlPrep43       = 'SELECT "foo"."bar" AS "bar" FROM "foo" AS "x"';
        $sqlStr43        = 'SELECT "foo"."bar" AS "bar" FROM "foo" AS "x"';
        $internalTests43 = [
            'processSelect' => [[['"foo"."bar"', '"bar"']], '"foo" AS "x"'],
        ];

        $select44 = new Select();
        $select44->from('foo')->where('a = b');
        $select44b = new Select();
        $select44b->from('bar')->where('c = d');
        $select44->combine($select44b, Select::COMBINE_UNION, 'ALL');
        $sqlPrep44       = '( SELECT "foo".* FROM "foo" WHERE a = b ) UNION ALL ( SELECT "bar".* FROM "bar" WHERE c = d )';
        $sqlStr44        = '( SELECT "foo".* FROM "foo" WHERE a = b ) UNION ALL ( SELECT "bar".* FROM "bar" WHERE c = d )';
        $internalTests44 = [
            'processCombine' => ['UNION ALL', 'SELECT "bar".* FROM "bar" WHERE c = d'],
        ];

        // limit with offset
        $select45 = new Select();
        $select45->from('foo')->limit("5")->offset("10");
        $sqlPrep45       = 'SELECT "foo".* FROM "foo" LIMIT ? OFFSET ?';
        $sqlStr45        = 'SELECT "foo".* FROM "foo" LIMIT \'5\' OFFSET \'10\'';
        $params45        = ['limit' => 5, 'offset' => 10];
        $internalTests45 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processLimit'  => ['?'],
            'processOffset' => ['?'],
        ];

        // functions without table
        $select46 = new Select();
        $select46->columns([
            new Expression('SOME_DB_FUNCTION_ONE()'),
            'foo' => new Expression('SOME_DB_FUNCTION_TWO()'),
        ]);
        $sqlPrep46       = 'SELECT SOME_DB_FUNCTION_ONE() AS Expression1, SOME_DB_FUNCTION_TWO() AS "foo"';
        $sqlStr46        = 'SELECT SOME_DB_FUNCTION_ONE() AS Expression1, SOME_DB_FUNCTION_TWO() AS "foo"';
        $params46        = [];
        $internalTests46 = [];

        // limit with big offset and limit
        $select47 = new Select();
        $select47->from('foo')->limit("10000000000000000000")->offset("10000000000000000000");
        $sqlPrep47       = 'SELECT "foo".* FROM "foo" LIMIT ? OFFSET ?';
        $sqlStr47        = 'SELECT "foo".* FROM "foo" LIMIT \'10000000000000000000\' OFFSET \'10000000000000000000\'';
        $params47        = ['limit' => 10000000000000000000, 'offset' => 10000000000000000000];
        $internalTests47 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processLimit'  => ['?'],
            'processOffset' => ['?'],
        ];

        //combine and union with order at the end
        $select48 = new Select();
        $select48->from('foo')->where('a = b');
        $select48b = new Select();
        $select48b->from('bar')->where('c = d');
        $select48->combine($select48b);

        $select48combined = new Select();
        $select48         = $select48combined->from(['sub' => $select48])->order('id DESC');
        $sqlPrep48        = 'SELECT "sub".* FROM (( SELECT "foo".* FROM "foo" WHERE a = b ) UNION ( SELECT "bar".* FROM "bar" WHERE c = d )) AS "sub" ORDER BY "id" DESC';
        $sqlStr48         = 'SELECT "sub".* FROM (( SELECT "foo".* FROM "foo" WHERE a = b ) UNION ( SELECT "bar".* FROM "bar" WHERE c = d )) AS "sub" ORDER BY "id" DESC';
        $internalTests48  = [
            'processCombine' => null,
        ];

        //Expression as joinName
        $select49 = new Select();
        $select49->from(new TableIdentifier('foo'))
            ->join(['bar' => new Expression('psql_function_which_returns_table')], 'foo.id = bar.fooid');
        $sqlPrep49       = 'SELECT "foo".*, "bar".* FROM "foo" INNER JOIN psql_function_which_returns_table AS "bar" ON "foo"."id" = "bar"."fooid"';
        $sqlStr49        = 'SELECT "foo".*, "bar".* FROM "foo" INNER JOIN psql_function_which_returns_table AS "bar" ON "foo"."id" = "bar"."fooid"';
        $internalTests49 = [
            'processSelect' => [[['"foo".*'], ['"bar".*']], '"foo"'],
            'processJoins'  => [[['INNER', 'psql_function_which_returns_table AS "bar"', '"foo"."id" = "bar"."fooid"']]],
        ];

        // Test generic predicate is appended with AND
        $select50 = new Select();
        $select50->from(new TableIdentifier('foo'))
            ->where
            ->nest
            ->isNull('bar')
            ->and
            ->predicate(new Predicate\Literal('1=1'));
        $sqlPrep50       = 'SELECT "foo".* FROM "foo" WHERE ("bar" IS NULL AND 1=1)';
        $sqlStr50        = 'SELECT "foo".* FROM "foo" WHERE ("bar" IS NULL AND 1=1)';
        $internalTests50 = [];

        // Test generic predicate is appended with OR
        $select51 = new Select();
        $select51->from(new TableIdentifier('foo'))
            ->where
            ->nest
            ->isNull('bar')
            ->or
            ->predicate(new Predicate\Literal('1=1'));
        $sqlPrep51       = 'SELECT "foo".* FROM "foo" WHERE ("bar" IS NULL OR 1=1)';
        $sqlStr51        = 'SELECT "foo".* FROM "foo" WHERE ("bar" IS NULL OR 1=1)';
        $internalTests51 = [];

        /**
         * @link https://github.com/zendframework/zf2/issues/7222
         */
        $select52 = new Select();
        $select52->from('foo')->join('zac', '(catalog_category_website.category_id = catalog_category.category_id)');
        $sqlPrep52       = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON ("catalog_category_website"."category_id" = "catalog_category"."category_id")';
        $sqlStr52        = 'SELECT "foo".*, "zac".* FROM "foo" INNER JOIN "zac" ON ("catalog_category_website"."category_id" = "catalog_category"."category_id")';
        $internalTests52 = [
            'processSelect' => [[['"foo".*'], ['"zac".*']], '"foo"'],
            'processJoins'  => [
                [
                    ['INNER', '"zac"', '("catalog_category_website"."category_id" = "catalog_category"."category_id")'],
                ],
            ],
        ];

        $subSelect53 = new Select();
        $subSelect53->from('bar')->columns(['id'])->limit(10)->offset(9);
        $select53 = new Select();
        $select53->from('foo')->where(new In('bar_id', $subSelect53))->limit(11)->offset(12);
        $params53        = ['limit' => 11, 'offset' => 12, 'subselect1limit' => 10, 'subselect1offset' => 9];
        $sqlPrep53       = 'SELECT "foo".* FROM "foo" WHERE "bar_id" IN (SELECT "bar"."id" AS "id" FROM "bar" LIMIT :subselect1limit OFFSET :subselect1offset) LIMIT :limit OFFSET :offset';
        $sqlStr53        = 'SELECT "foo".* FROM "foo" WHERE "bar_id" IN (SELECT "bar"."id" AS "id" FROM "bar" LIMIT \'10\' OFFSET \'9\') LIMIT \'11\' OFFSET \'12\'';
        $internalTests53 = [
            'processSelect' => [[['"foo".*']], '"foo"'],
            'processWhere'  => ['"bar_id" IN (SELECT "bar"."id" AS "id" FROM "bar" LIMIT ? OFFSET ?)'],
            'processLimit'  => ['?'],
            'processOffset' => ['?'],
        ];

        // join with alternate type full outer
        $select54 = new Select();
        $select54->from('foo')->join('zac', 'm = n', ['bar', 'baz'], Select::JOIN_FULL_OUTER);
        $sqlPrep54       = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" FULL OUTER JOIN "zac" ON "m" = "n"';
        $sqlStr54        = 'SELECT "foo".*, "zac"."bar" AS "bar", "zac"."baz" AS "baz" FROM "foo" FULL OUTER JOIN "zac" ON "m" = "n"';
        $internalTests54 = [
            'processSelect' => [[['"foo".*'], ['"zac"."bar"', '"bar"'], ['"zac"."baz"', '"baz"']], '"foo"'],
            'processJoins'  => [[['FULL OUTER', '"zac"', '"m" = "n"']]],
        ];

        /**
         * $select = the select object
         * $sqlPrep = the sql as a result of preparation
         * $params = the param container contents result of preparation
         * $sqlStr = the sql as a result of getting a string back
         * $internalTests what the internal functions should return (safe-guarding extension)
         */

        return [
            //    $select    $sqlPrep    $params     $sqlStr    $internalTests    use named param
            [$select0, $sqlPrep0, [], $sqlStr0, $internalTests0, false],
            [$select1, $sqlPrep1, [], $sqlStr1, $internalTests1, false],
            [$select2, $sqlPrep2, [], $sqlStr2, $internalTests2, false],
            [$select3, $sqlPrep3, [], $sqlStr3, $internalTests3, false],
            [$select4, $sqlPrep4, [], $sqlStr4, $internalTests4, false],
            [$select5, $sqlPrep5, [], $sqlStr5, $internalTests5, false],
            [$select6, $sqlPrep6, [], $sqlStr6, $internalTests6, false],
            [$select7, $sqlPrep7, [], $sqlStr7, $internalTests7, false],
            [$select8, $sqlPrep8, [], $sqlStr8, $internalTests8, false],
            [$select9,  $sqlPrep9,  $params9,   $sqlStr9,  $internalTests9, false],
            [$select10, $sqlPrep10, [], $sqlStr10, $internalTests10, false],
            [$select11, $sqlPrep11, [], $sqlStr11, $internalTests11, false],
            [$select12, $sqlPrep12, [], $sqlStr12, $internalTests12, false],
            [$select13, $sqlPrep13, [], $sqlStr13, $internalTests13, false],
            [$select14, $sqlPrep14, [], $sqlStr14, $internalTests14, false],
            [$select15, $sqlPrep15, [], $sqlStr15, $internalTests15, false],
            [$select16, $sqlPrep16, $params16,  $sqlStr16, $internalTests16, false],
            [$select17, $sqlPrep17, [], $sqlStr17, $internalTests17, false],
            [$select18, $sqlPrep18, [], $sqlStr18, $internalTests18, false],
            [$select19, $sqlPrep19, [], $sqlStr19, $internalTests19, false],
            [$select20, $sqlPrep20, [], $sqlStr20, $internalTests20, false],
            [$select21, $sqlPrep21, $params21,  $sqlStr21, $internalTests21, false],
            [$select22, $sqlPrep22, [], $sqlStr22, $internalTests22, false],
            [$select23, $sqlPrep23, [], $sqlStr23, $internalTests23, false],
            [$select24, $sqlPrep24, [], $sqlStr24, $internalTests24, false],
            [$select25, $sqlPrep25, [], $sqlStr25, $internalTests25, false],
            [$select26, $sqlPrep26, $params26,  $sqlStr26, $internalTests26, false],
            [$select27, $sqlPrep27, $params27,  $sqlStr27, $internalTests27, false],
            [$select28, $sqlPrep28, [], $sqlStr28, $internalTests28, false],
            [$select29, $sqlPrep29, [], $sqlStr29, $internalTests29, false],
            [$select30, $sqlPrep30, [], $sqlStr30, $internalTests30, false],
            [$select31, $sqlPrep31, [], $sqlStr31, $internalTests31, false],
            [$select32, $sqlPrep32, [], $sqlStr32, $internalTests32, false],
            [$select33, $sqlPrep33, [], $sqlStr33, $internalTests33, false],
            [$select34, $sqlPrep34, [], $sqlStr34, $internalTests34, false],
            [$select35, $sqlPrep35, [], $sqlStr35, $internalTests35, false],
            [$select36, $sqlPrep36, [], $sqlStr36, $internalTests36, true],
            [$select37, $sqlPrep37, [], $sqlStr37, $internalTests37, false],
            [$select38, $sqlPrep38, [], $sqlStr38, $internalTests38, false],
            [$select39, $sqlPrep39, [], $sqlStr39, $internalTests39, false],
            [$select40, $sqlPrep40, [], $sqlStr40, $internalTests40, false],
            [$select41, $sqlPrep41, [], $sqlStr41, $internalTests41, false],
            [$select42, $sqlPrep42, [], $sqlStr42, $internalTests42, false],
            [$select43, $sqlPrep43, [], $sqlStr43, $internalTests43, false],
            [$select44, $sqlPrep44, [], $sqlStr44, $internalTests44, false],
            [$select45, $sqlPrep45, $params45,  $sqlStr45, $internalTests45, false],
            [$select46, $sqlPrep46, $params46,  $sqlStr46, $internalTests46, false],
            [$select47, $sqlPrep47, $params47,  $sqlStr47, $internalTests47, false],
            [$select48, $sqlPrep48, [], $sqlStr48, $internalTests48, false],
            [$select49, $sqlPrep49, [], $sqlStr49, $internalTests49, false],
            [$select50, $sqlPrep50, [], $sqlStr50, $internalTests50, false],
            [$select51, $sqlPrep51, [], $sqlStr51, $internalTests51, false],
            [$select52, $sqlPrep52, [], $sqlStr52, $internalTests52, false],
            [$select53, $sqlPrep53, $params53, $sqlStr53, $internalTests53, true],
            [$select54, $sqlPrep54, [], $sqlStr54, $internalTests54, false],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    public function testFromThrowsExceptionWhenTableReadOnly(): void
    {
        $select = new Select('foo'); // Creating with table makes it read-only

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Since this object was created with a table and/or schema in the constructor, it is read only.'
        );
        $select->from('bar');
    }

    public function testFromThrowsExceptionForInvalidTableType(): void
    {
        $select = new Select();

        $this->expectException(TypeError::class);
        /** @noinspection ALL */
        $select->from(123);
    }

    public function testFromThrowsExceptionForInvalidArrayFormat(): void
    {
        $select = new Select();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('from() expects $table as an array is a single element associative array');
        $select->from(['foo', 'bar']); // Numeric array instead of associative
    }

    public function testSetSpecificationThrowsExceptionForInvalidName(): void
    {
        $select = new Select();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid specification name.');
        $select->setSpecification('invalid_spec', 'some spec');
    }

    public function testGetThrowsExceptionForInvalidProperty(): void
    {
        $select = new Select();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid magic property for this object');
        /** @noinspection ALL */
        $value = $select->invalidProperty;
    }
}
