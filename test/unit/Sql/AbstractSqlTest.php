<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\SelectDecorator;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

use function count;
use function current;
use function key;
use function next;
use function preg_match;
use function uniqid;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(AbstractSql::class, 'getSqlString')]
#[CoversMethod(AbstractSql::class, 'buildSqlString')]
#[CoversMethod(AbstractSql::class, 'renderTable')]
#[CoversMethod(AbstractSql::class, 'processExpression')]
#[CoversMethod(AbstractSql::class, 'processExpressionOrSelect')]
#[CoversMethod(AbstractSql::class, 'processExpressionParameterName')]
#[CoversMethod(AbstractSql::class, 'createSqlFromSpecificationAndParameters')]
#[CoversMethod(AbstractSql::class, 'processSubSelect')]
#[CoversMethod(AbstractSql::class, 'processJoin')]
#[CoversMethod(AbstractSql::class, 'processIdentifiersArgument')]
#[CoversMethod(AbstractSql::class, 'flattenExpressionValues')]
#[CoversMethod(AbstractSql::class, 'resolveColumnValue')]
#[CoversMethod(AbstractSql::class, 'resolveTable')]
#[CoversMethod(AbstractSql::class, 'localizeVariables')]
final class AbstractSqlTest extends TestCase
{
    protected AbstractSql&MockObject $abstractSql;

    protected DriverInterface&MockObject $mockDriver;

    /**
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->abstractSql = $this->getMockBuilder(AbstractSql::class)->onlyMethods([])->getMock();

        $this->mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $this->mockDriver
            ->expects($this->any())
            ->method('getPrepareType')
            ->willReturn(DriverInterface::PARAMETERIZATION_NAMED);
        $this->mockDriver
            ->expects($this->any())
            ->method('formatParameterName')
            ->willReturnCallback(fn($x): string => ':' . $x);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWithoutParameterContainer(): void
    {
        $expression   = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        self::assertEquals("\"x\" > '5' AND y < '10'", $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWithParameterContainerAndParameterizationTypeNamed(): void
    {
        $parameterContainer = new ParameterContainer();
        $expression         = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams       = $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        // Verify SQL uses named parameters
        self::assertMatchesRegularExpression('#"x" > :expr\d+Param1 AND y < :expr\d+Param2#', $sqlAndParams);

        // Verify parameter names and values
        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumber = $matches[1];

        self::assertMatchesRegularExpression('#expr\d+Param1#', key($parameters));
        self::assertEquals(5, current($parameters));
        next($parameters);
        self::assertMatchesRegularExpression('#expr\d+Param2#', key($parameters));
        self::assertEquals(10, current($parameters));

        // Verify next invocation increments expression number
        $parameterContainer = new ParameterContainer();
        $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumberNext = $matches[1];

        self::assertEquals(1, (int) $expressionNumberNext - (int) $expressionNumber);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWorksWithExpressionContainingStringParts(): void
    {
        $expression = new Predicate\Expression('x = ?', 5);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($predicateSet);

        self::assertEquals("(x = '5')", $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWorksWithExpressionContainingSelectObject(): void
    {
        $select = new Select();
        $select->from('x')->where->like('bar', 'Foo%');
        $expression = new Predicate\In('x', $select);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($predicateSet);

        self::assertEquals('("x" IN (SELECT "x".* FROM "x" WHERE "bar" LIKE \'Foo%\'))', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWorksWithExpressionContainingExpressionObject(): void
    {
        $expression = new Predicate\Operator(
            'release_date',
            '=',
            new Expression('FROM_UNIXTIME(?)', 100000000)
        );

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);
        self::assertEquals('"release_date" = FROM_UNIXTIME(\'100000000\')', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Group('7407')]
    public function testProcessExpressionWorksWithExpressionObjectWithPercentageSigns(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);
        $sqlString        = $this->invokeProcessExpressionMethod($expression);

        self::assertSame($expressionString, $sqlString);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWorksWithNamedParameterPrefix(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = uniqid();
        $expression           = new Expression('FROM_UNIXTIME(?)', [10000000]);
        $this->invokeProcessExpressionMethod($expression, $parameterContainer, $namedParameterPrefix);

        self::assertSame($namedParameterPrefix . '1', (string) key($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWorksWithNamedParameterPrefixContainingWhitespace(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = "string\ncontaining white space";
        $expression           = new Expression('FROM_UNIXTIME(?)', [10000000]);
        $this->invokeProcessExpressionMethod($expression, $parameterContainer, $namedParameterPrefix);

        self::assertSame('string__containing__white__space1', key($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveColumnValueWithNull(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'resolveColumnValue');

        $result = $method->invoke(
            $this->abstractSql,
            null,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
            null
        );

        self::assertEquals('NULL', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveColumnValueWithSelect(): void
    {
        $select = new Select('foo');
        $method = new ReflectionMethod($this->abstractSql, 'resolveColumnValue');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
            null
        );

        self::assertStringContainsString('SELECT', $result);
        self::assertStringStartsWith('(', $result);
        self::assertStringEndsWith(')', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveColumnValueWithArrayAndFromTable(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'resolveColumnValue');

        $result = $method->invoke(
            $this->abstractSql,
            [
                'column'       => 'id',
                'isIdentifier' => true,
                'fromTable'    => 'table.',
            ],
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
            null
        );

        self::assertStringContainsString('table.', $result);
        self::assertStringContainsString('id', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveTableWithTableIdentifierAndSchema(): void
    {
        $table  = new TableIdentifier('users', 'public');
        $method = new ReflectionMethod($this->abstractSql, 'resolveTable');

        $result = $method->invoke(
            $this->abstractSql,
            $table,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null
        );

        self::assertStringContainsString('public', $result);
        self::assertStringContainsString('users', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveTableWithSelect(): void
    {
        $select = new Select('foo');
        $method = new ReflectionMethod($this->abstractSql, 'resolveTable');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null
        );

        self::assertStringStartsWith('(', $result);
        self::assertStringEndsWith(')', $result);
        self::assertStringContainsString('SELECT', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessSubSelectWithParameterContainer(): void
    {
        $select = new Select('foo');
        $select->where(['id' => 5]);

        $method = new ReflectionMethod($this->abstractSql, 'processSubSelect');

        $parameterContainer = new ParameterContainer();
        $result             = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            $parameterContainer
        );

        self::assertStringContainsString('SELECT', $result);
        self::assertGreaterThan(0, count($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessSubSelectWithoutParameterContainer(): void
    {
        $select = new Select('foo');

        $method = new ReflectionMethod($this->abstractSql, 'processSubSelect');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null
        );

        self::assertStringContainsString('SELECT', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWithValuesArgument(): void
    {
        $expression = new Expression(
            '? IN (?, ?, ?)',
            [
                new Argument\Identifier('id'),
                new Argument\Value(1),
                new Argument\Value(2),
                new Argument\Value(3),
            ]
        );

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        self::assertStringContainsString("'1'", $sqlAndParams);
        self::assertStringContainsString("'2'", $sqlAndParams);
        self::assertStringContainsString("'3'", $sqlAndParams);
        self::assertStringContainsString('"id"', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWithIdentifiersArgument(): void
    {
        $expression = new Expression('? IN (SELECT col1, col2 FROM bar)', [
            Argument::identifiers(['col1', 'col2']),
        ]);

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        self::assertStringContainsString('"col1"', $sqlAndParams);
        self::assertStringContainsString('"col2"', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSqlFromSpecificationThrowsOnParameterCountMismatch(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $specifications = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A number of parameters was found that is not supported by this specification');
        $method->invoke($this->abstractSql, $specifications, ['col1', 'table', 'extra']);
    }

    /**
     * @throws ReflectionException
     */
    protected function invokeProcessExpressionMethod(
        ExpressionInterface $expression,
        ParameterContainer|null $parameterContainer = null,
        string|null $namedParameterPrefix = null
    ): string|StatementContainer {
        $method = new ReflectionMethod($this->abstractSql, 'processExpression');
        return $method->invoke(
            $this->abstractSql,
            $expression,
            new TrustingSql92Platform(),
            $this->mockDriver,
            $parameterContainer,
            $namedParameterPrefix
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinWithArrayAlias(): void
    {
        $join = new Join();
        $join->join(['b' => 'bar'], 'foo.id = b.foo_id');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertNotNull($result);
        self::assertStringContainsString('AS', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinWithTableIdentifier(): void
    {
        $join = new Join();
        $join->join(new TableIdentifier('bar', 'myschema'), 'foo.id = bar.foo_id');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertNotNull($result);
        self::assertStringContainsString('"myschema"', $result[0][0][1]);
        self::assertStringContainsString('"bar"', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinWithPredicateExpressionOnClause(): void
    {
        $join = new Join();
        $join->join('bar', new Predicate\Expression('foo.id = bar.foo_id AND bar.active = 1'));

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertNotNull($result);
        self::assertStringContainsString('foo.id = bar.foo_id', $result[0][0][2]);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinReturnsNullWhenEmpty(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            null,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertNull($result);
    }

    /**
     * @throws ReflectionException
     */
    public function testRenderTableWithAlias(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'renderTable');
        $result = $method->invoke($this->abstractSql, '"foo"', '"f"');

        self::assertSame('"foo" AS "f"', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinWithExpressionNameViaArray(): void
    {
        $join = new Join();
        $join->join(['x' => new Expression('LATERAL(SELECT 1)')], 'true');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertStringContainsString('LATERAL(SELECT 1)', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJoinWithSelectSubqueryViaArray(): void
    {
        $subselect = new Select('bar');
        $join      = new Join();
        $join->join(['b' => $subselect], 'foo.id = b.foo_id');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null
        );

        self::assertStringContainsString('SELECT', $result[0][0][1]);
        self::assertStringContainsString('AS', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSqlFromSpecWithCombinedByScalarParam(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec   = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];
        $params = [['col1'], 'table1'];

        $result = $method->invoke($this->abstractSql, $spec, $params);

        self::assertSame('SELECT col1 FROM table1', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSqlFromSpecWithCombinedByThrowsOnUnsupportedCount(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec   = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];
        $params = [[['a', 'b']], 'table1'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A number of parameters (2)');
        $method->invoke($this->abstractSql, $spec, $params);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSqlFromSpecWithNonCombinedByParam(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec   = [
            'FROM %1$s' => [
                [1 => '%1$s'],
            ],
        ];
        $params = [['my_table']];

        $result = $method->invoke($this->abstractSql, $spec, $params);

        self::assertSame('FROM my_table', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSqlFromSpecNonCombinedByThrowsOnUnsupportedCount(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec   = [
            'FROM %1$s' => [
                [1 => '%1$s'],
            ],
        ];
        $params = [['a', 'b']];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A number of parameters (2)');
        $method->invoke($this->abstractSql, $spec, $params);
    }

    public function testProcessExpressionThrowsOnUnknownArgumentType(): void
    {
        $unknownArg = new class implements ArgumentInterface {
            public function getType(): ArgumentType
            {
                return ArgumentType::Value;
            }

            public function getValue(): string
            {
                return 'test';
            }

            public function getSpecification(): string
            {
                return '%s';
            }
        };

        $expression = new Expression('?', [$unknownArg]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown argument type');
        $this->invokeProcessExpressionMethod($expression);
    }

    public function testResolveColumnValueWithNamedParameterPrefix(): void
    {
        $select = new Select('users');
        $select->columns(['id']);
        $select->where(new Predicate\In('status', [1, 2]));

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->method('formatParameterName')
            ->willReturnCallback(fn(string $name): string => ':' . $name);

        $parameterContainer = new ParameterContainer();
        $mockStatement      = $this->createMock(StatementInterface::class);
        $mockStatement->method('getParameterContainer')->willReturn($parameterContainer);
        $mockStatement->method('setSql')->willReturnSelf();

        $adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockDriver, new TrustingSql92Platform()])
            ->getMock();
        $adapter->method('getDriver')->willReturn($mockDriver);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());

        $select->prepareStatement($adapter, $mockStatement);

        self::assertGreaterThanOrEqual(2, $parameterContainer->count());
    }

    public function testLocalizeVariablesCopiesSubjectProperties(): void
    {
        $decorator = new SelectDecorator();
        $select    = new Select('users');
        $select->columns(['id', 'name']);
        $decorator->setSubject($select);

        $sql = $decorator->getSqlString(new TrustingSql92Platform());

        self::assertStringContainsString('"users"', $sql);
        self::assertStringContainsString('"id"', $sql);
    }

    public function testProcessSubSelectUsesDecoratorWhenPlatformDecorator(): void
    {
        $decorator = new SelectDecorator();
        $outer     = new Select('foo');
        $outer->where(['x' => new Select('bar')]);

        $decorator->setSubject($outer);

        $sql = $decorator->getSqlString(new TrustingSql92Platform());

        self::assertStringContainsString('SELECT "bar"', $sql);
        self::assertStringContainsString('SELECT "foo"', $sql);
    }

    public function testFlattenExpressionValuesViaInPredicate(): void
    {
        $select = new Select('users');
        $select->where(new Predicate\In('id', [1, 2, 3]));

        $sql = $select->getSqlString(new TrustingSql92Platform());

        self::assertStringContainsString("\"id\" IN ('1', '2', '3')", $sql);
    }

    public function testFlattenExpressionValuesViaInPredicateWithParameterContainer(): void
    {
        $select = new Select('users');
        $select->where(new Predicate\In('id', [1, 2, 3]));

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->method('formatParameterName')
            ->willReturnCallback(fn(string $name): string => ':' . $name);

        $parameterContainer = new ParameterContainer();
        $mockStatement      = $this->createMock(StatementInterface::class);
        $mockStatement->method('getParameterContainer')->willReturn($parameterContainer);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockDriver, new TrustingSql92Platform()])
            ->getMock();
        $adapter->method('getDriver')->willReturn($mockDriver);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());

        $select->prepareStatement($adapter, $mockStatement);

        self::assertSame(3, $parameterContainer->count());
    }
}
