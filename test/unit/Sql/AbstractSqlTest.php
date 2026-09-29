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
use PHPUnit\Framework\Attributes\Test;
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
use function sprintf;
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
     * @throws ReflectionException
     */
    #[Test]
    public function createSqlFromSpecificationThrowsOnParameterCountMismatch(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $specifications = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNSUPPORTED_PARAMETER_COUNT);
        $method->invoke($this->abstractSql, $specifications, ['col1', 'table', 'extra']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function createSqlFromSpecNonCombinedByThrowsOnUnsupportedCount(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec = [
            'FROM %1$s' => [
                [1 => '%1$s'],
            ],
        ];
        $params = [['a', 'b']];

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNSUPPORTED_PARAMETER_COUNT_OF, 2));
        $method->invoke($this->abstractSql, $spec, $params);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function createSqlFromSpecWithCombinedByScalarParam(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];
        $params = [['col1'], 'table1'];

        $result = $method->invoke($this->abstractSql, $spec, $params);

        static::assertSame('SELECT col1 FROM table1', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function createSqlFromSpecWithCombinedByThrowsOnUnsupportedCount(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec = [
            'SELECT %1$s FROM %2$s' => [
                [1 => '%1$s', 'combinedby' => ', '],
                null,
            ],
        ];
        $params = [[['a', 'b']], 'table1'];

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNSUPPORTED_PARAMETER_COUNT_OF, 2));
        $method->invoke($this->abstractSql, $spec, $params);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function createSqlFromSpecWithNonCombinedByParam(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'createSqlFromSpecificationAndParameters');

        $spec = [
            'FROM %1$s' => [
                [1 => '%1$s'],
            ],
        ];
        $params = [['my_table']];

        $result = $method->invoke($this->abstractSql, $spec, $params);

        static::assertSame('FROM my_table', $result);
    }

    #[Test]
    public function flattenExpressionValuesViaInPredicate(): void
    {
        $select = new Select('users');
        $select->where(new Predicate\In('id', [1, 2, 3]));

        $sql = $select->getSqlString(new TrustingSql92Platform());

        static::assertStringContainsString("\"id\" IN ('1', '2', '3')", $sql);
    }

    #[Test]
    public function flattenExpressionValuesViaInPredicateWithParameterContainer(): void
    {
        $select = new Select('users');
        $select->where(new Predicate\In('id', [1, 2, 3]));

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->method('formatParameterName')
            ->willReturnCallback(static fn(string $name): string => ":{$name}");

        $parameterContainer = new ParameterContainer();
        $mockStatement      = $this->createMock(StatementInterface::class);
        $mockStatement->method('getParameterContainer')->willReturn($parameterContainer);

        $adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockDriver, new TrustingSql92Platform()])
            ->getMock();
        $adapter->method('getDriver')->willReturn($mockDriver);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());

        $select->prepareStatement($adapter, $mockStatement);

        static::assertSame(3, $parameterContainer->count());
    }

    #[Test]
    public function localizeVariablesCopiesSubjectProperties(): void
    {
        $decorator = new SelectDecorator();
        $select    = new Select('users');
        $select->columns(['id', 'name']);
        $decorator->setSubject($select);

        $sql = $decorator->getSqlString(new TrustingSql92Platform());

        static::assertStringContainsString('"users"', $sql);
        static::assertStringContainsString('"id"', $sql);
    }

    #[Test]
    public function processExpressionThrowsOnUnknownArgumentType(): void
    {
        $unknownArg = new class implements ArgumentInterface {
            public function getSpecification(): string
            {
                return '%s';
            }

            public function getType(): ArgumentType
            {
                return ArgumentType::Value;
            }

            public function getValue(): string
            {
                return 'test';
            }
        };

        $expression = new Expression('?', [$unknownArg]);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::UNKNOWN_ARGUMENT_TYPE);
        $this->invokeProcessExpressionMethod($expression);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWithIdentifiersArgument(): void
    {
        $expression = new Expression('? IN (SELECT col1, col2 FROM bar)', [
            Argument::identifiers(['col1', 'col2']),
        ]);

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        static::assertStringContainsString('"col1"', $sqlAndParams);
        static::assertStringContainsString('"col2"', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWithoutParameterContainer(): void
    {
        $expression   = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        static::assertSame("\"x\" > '5' AND y < '10'", $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWithParameterContainerAndParameterizationTypeNamed(): void
    {
        $parameterContainer = new ParameterContainer();
        $expression         = new Expression('? > ? AND y < ?', [new Identifier('x'), 5, 10]);
        $sqlAndParams       = $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        // Verify SQL uses named parameters
        static::assertMatchesRegularExpression('#"x" > :expr\d+Param1 AND y < :expr\d+Param2#', $sqlAndParams);

        // Verify parameter names and values
        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumber = $matches[1];

        static::assertMatchesRegularExpression('#expr\d+Param1#', key($parameters));
        static::assertSame(5, current($parameters));
        next($parameters);
        static::assertMatchesRegularExpression('#expr\d+Param2#', key($parameters));
        static::assertSame(10, current($parameters));

        // Verify next invocation increments expression number
        $parameterContainer = new ParameterContainer();
        $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        preg_match('#expr(\d+)Param1#', key($parameters), $matches);
        $expressionNumberNext = $matches[1];

        static::assertSame(1, (int) $expressionNumberNext - (int) $expressionNumber);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWithValuesArgument(): void
    {
        $expression = new Expression(
            '? IN (?, ?, ?)',
            [
                new Argument\Identifier('id'),
                new Argument\Value(1),
                new Argument\Value(2),
                new Argument\Value(3),
            ],
        );

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        static::assertStringContainsString("'1'", $sqlAndParams);
        static::assertStringContainsString("'2'", $sqlAndParams);
        static::assertStringContainsString("'3'", $sqlAndParams);
        static::assertStringContainsString('"id"', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWorksWithExpressionContainingExpressionObject(): void
    {
        $expression = new Predicate\Operator(
            'release_date',
            '=',
            new Expression('FROM_UNIXTIME(?)', 100_000_000),
        );

        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);
        static::assertSame('"release_date" = FROM_UNIXTIME(\'100000000\')', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWorksWithExpressionContainingSelectObject(): void
    {
        $select = new Select();
        $select->from('x')->where->like('bar', 'Foo%');
        $expression = new Predicate\In('x', $select);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($predicateSet);

        static::assertSame('("x" IN (SELECT "x".* FROM "x" WHERE "bar" LIKE \'Foo%\'))', $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWorksWithExpressionContainingStringParts(): void
    {
        $expression = new Predicate\Expression('x = ?', 5);

        $predicateSet = new Predicate\PredicateSet([new Predicate\PredicateSet([$expression])]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($predicateSet);

        static::assertSame("(x = '5')", $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    #[Group('7407')]
    public function processExpressionWorksWithExpressionObjectWithPercentageSigns(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);
        $sqlString        = $this->invokeProcessExpressionMethod($expression);

        static::assertSame($expressionString, $sqlString);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWorksWithNamedParameterPrefix(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = uniqid();
        $expression           = new Expression('FROM_UNIXTIME(?)', [10_000_000]);
        $this->invokeProcessExpressionMethod($expression, $parameterContainer, $namedParameterPrefix);

        static::assertSame("{$namedParameterPrefix}1", (string) key($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processExpressionWorksWithNamedParameterPrefixContainingWhitespace(): void
    {
        $parameterContainer   = new ParameterContainer();
        $namedParameterPrefix = "string\ncontaining white space";
        $expression           = new Expression('FROM_UNIXTIME(?)', [10_000_000]);
        $this->invokeProcessExpressionMethod($expression, $parameterContainer, $namedParameterPrefix);

        static::assertSame('string__containing__white__space1', key($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinReturnsNullWhenEmpty(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            null,
            new TrustingSql92Platform(),
            null,
            null,
        );

        static::assertNull($result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinWithArrayAlias(): void
    {
        $join = new Join();
        $join->join(['b' => 'bar'], 'foo.id = b.foo_id');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null,
        );

        static::assertNotNull($result);
        static::assertStringContainsString('AS', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinWithExpressionNameViaArray(): void
    {
        $join = new Join();
        $join->join(['x' => new Expression('LATERAL(SELECT 1)')], 'true');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null,
        );

        static::assertStringContainsString('LATERAL(SELECT 1)', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinWithPredicateExpressionOnClause(): void
    {
        $join = new Join();
        $join->join('bar', new Predicate\Expression('foo.id = bar.foo_id AND bar.active = 1'));

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null,
        );

        static::assertNotNull($result);
        static::assertStringContainsString('foo.id = bar.foo_id', $result[0][0][2]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinWithSelectSubqueryViaArray(): void
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
            null,
        );

        static::assertStringContainsString('SELECT', $result[0][0][1]);
        static::assertStringContainsString('AS', $result[0][0][1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processJoinWithTableIdentifier(): void
    {
        $join = new Join();
        $join->join(new TableIdentifier('bar', 'myschema'), 'foo.id = bar.foo_id');

        $method = new ReflectionMethod($this->abstractSql, 'processJoin');
        $result = $method->invoke(
            $this->abstractSql,
            $join,
            new TrustingSql92Platform(),
            null,
            null,
        );

        static::assertNotNull($result);
        static::assertStringContainsString('"myschema"', $result[0][0][1]);
        static::assertStringContainsString('"bar"', $result[0][0][1]);
    }

    #[Test]
    public function processSubSelectUsesDecoratorWhenPlatformDecorator(): void
    {
        $decorator = new SelectDecorator();
        $outer     = new Select('foo');
        $outer->where(['x' => new Select('bar')]);

        $decorator->setSubject($outer);

        $sql = $decorator->getSqlString(new TrustingSql92Platform());

        static::assertStringContainsString('SELECT "bar"', $sql);
        static::assertStringContainsString('SELECT "foo"', $sql);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processSubSelectWithoutParameterContainer(): void
    {
        $select = new Select('foo');

        $method = new ReflectionMethod($this->abstractSql, 'processSubSelect');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
        );

        static::assertStringContainsString('SELECT', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function processSubSelectWithParameterContainer(): void
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
            $parameterContainer,
        );

        static::assertStringContainsString('SELECT', $result);
        static::assertGreaterThan(0, count($parameterContainer->getNamedArray()));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function renderTableWithAlias(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'renderTable');
        $result = $method->invoke($this->abstractSql, '"foo"', '"f"');

        static::assertSame('"foo" AS "f"', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function resolveColumnValueWithArrayAndFromTable(): void
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
            null,
        );

        static::assertStringContainsString('table.', $result);
        static::assertStringContainsString('id', $result);
    }

    #[Test]
    public function resolveColumnValueWithNamedParameterPrefix(): void
    {
        $select = new Select('users');
        $select->columns(['id']);
        $select->where(new Predicate\In('status', [1, 2]));

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->method('formatParameterName')
            ->willReturnCallback(static fn(string $name): string => ":{$name}");

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

        static::assertGreaterThanOrEqual(2, $parameterContainer->count());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function resolveColumnValueWithNull(): void
    {
        $method = new ReflectionMethod($this->abstractSql, 'resolveColumnValue');

        $result = $method->invoke(
            $this->abstractSql,
            null,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
            null,
        );

        static::assertSame('NULL', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function resolveColumnValueWithSelect(): void
    {
        $select = new Select('foo');
        $method = new ReflectionMethod($this->abstractSql, 'resolveColumnValue');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
            null,
        );

        static::assertStringContainsString('SELECT', $result);
        static::assertStringStartsWith('(', $result);
        static::assertStringEndsWith(')', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function resolveTableWithSelect(): void
    {
        $select = new Select('foo');
        $method = new ReflectionMethod($this->abstractSql, 'resolveTable');

        $result = $method->invoke(
            $this->abstractSql,
            $select,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
        );

        static::assertStringStartsWith('(', $result);
        static::assertStringEndsWith(')', $result);
        static::assertStringContainsString('SELECT', $result);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function resolveTableWithTableIdentifierAndSchema(): void
    {
        $table  = new TableIdentifier('users', 'public');
        $method = new ReflectionMethod($this->abstractSql, 'resolveTable');

        $result = $method->invoke(
            $this->abstractSql,
            $table,
            new TrustingSql92Platform(),
            $this->mockDriver,
            null,
        );

        static::assertStringContainsString('public', $result);
        static::assertStringContainsString('users', $result);
    }

    /**
     * @throws ReflectionException
     */
    protected function invokeProcessExpressionMethod(
        ExpressionInterface $expression,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null,
    ): string|StatementContainer {
        $method = new ReflectionMethod($this->abstractSql, 'processExpression');
        return $method->invoke(
            $this->abstractSql,
            $expression,
            new TrustingSql92Platform(),
            $this->mockDriver,
            $parameterContainer,
            $namedParameterPrefix,
        );
    }

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
            ->willReturnCallback(static fn($x): string => ":{$x}");
    }
}
