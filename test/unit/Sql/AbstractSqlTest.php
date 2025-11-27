<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
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

#[CoversMethod(AbstractSql::class, 'getSqlString')]
#[CoversMethod(AbstractSql::class, 'buildSqlString')]
#[CoversMethod(AbstractSql::class, 'renderTable')]
#[CoversMethod(AbstractSql::class, 'processExpression')]
#[CoversMethod(AbstractSql::class, 'processExpressionValue')]
#[CoversMethod(AbstractSql::class, 'processExpressionOrSelect')]
#[CoversMethod(AbstractSql::class, 'processExpressionParameterName')]
#[CoversMethod(AbstractSql::class, 'createSqlFromSpecificationAndParameters')]
#[CoversMethod(AbstractSql::class, 'processSubSelect')]
#[CoversMethod(AbstractSql::class, 'processJoin')]
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
        $expression   = new Expression('? > ? AND y < ?', [Argument::identifier('x'), 5, 10]);
        $sqlAndParams = $this->invokeProcessExpressionMethod($expression);

        self::assertEquals("\"x\" > '5' AND y < '10'", $sqlAndParams);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessExpressionWithParameterContainerAndParameterizationTypeNamed(): void
    {
        $parameterContainer = new ParameterContainer();
        $expression         = new Expression('? > ? AND y < ?', [Argument::identifier('x'), 5, 10]);
        $sqlAndParams       = $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        // Verify SQL uses named parameters
        self::assertMatchesRegularExpression('#"x" > :expr\d\d\d\dParam1 AND y < :expr\d\d\d\dParam2#', $sqlAndParams);

        // Verify parameter names and values
        preg_match('#expr(\d\d\d\d)Param1#', key($parameters), $matches);
        $expressionNumber = $matches[1];

        self::assertMatchesRegularExpression('#expr\d\d\d\dParam1#', key($parameters));
        self::assertEquals(5, current($parameters));
        next($parameters);
        self::assertMatchesRegularExpression('#expr\d\d\d\dParam2#', key($parameters));
        self::assertEquals(10, current($parameters));

        // Verify next invocation increments expression number
        $parameterContainer = new ParameterContainer();
        $this->invokeProcessExpressionMethod($expression, $parameterContainer);

        $parameters = $parameterContainer->getNamedArray();

        preg_match('#expr(\d\d\d\d)Param1#', key($parameters), $matches);
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

        self::assertSame($namedParameterPrefix . '1', key($parameterContainer->getNamedArray()));
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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

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
    protected function invokeProcessExpressionMethod(
        ExpressionInterface $expression,
        ParameterContainer|null $parameterContainer = null,
        string|null $namedParameterPrefix = null
    ): string|StatementContainer {
        $method = new ReflectionMethod($this->abstractSql, 'processExpression');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        return $method->invoke(
            $this->abstractSql,
            $expression,
            new TrustingSql92Platform(),
            $this->mockDriver,
            $parameterContainer,
            $namedParameterPrefix
        );
    }
}
