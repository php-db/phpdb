<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Combine;
use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Select;
use PhpDbTest\AdapterTestTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversMethod(Combine::class, '__construct')]
#[CoversMethod(Combine::class, 'combine')]
#[CoversMethod(Combine::class, 'union')]
#[CoversMethod(Combine::class, 'except')]
#[CoversMethod(Combine::class, 'intersect')]
#[CoversMethod(Combine::class, 'buildSqlString')]
#[CoversMethod(Combine::class, 'alignColumns')]
#[CoversMethod(Combine::class, 'getRawState')]
final class CombineTest extends TestCase
{
    use AdapterTestTrait;

    protected Combine $combine;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->combine = new Combine();
    }

    public function testRejectsInvalidStatement(): void
    {
        $this->expectException(TypeError::class);

        /** @noinspection PhpParamsInspection */
        $this->combine->combine('foo');
    }

    public function testGetSqlString(): void
    {
        $this->combine
                ->union(new Select('t1'))
                ->intersect(new Select('t2'))
                ->except(new Select('t3'))
                ->union(new Select('t4'));

        self::assertEquals(
            // @codingStandardsIgnoreStart
            '(SELECT "t1".* FROM "t1") INTERSECT (SELECT "t2".* FROM "t2") EXCEPT (SELECT "t3".* FROM "t3") UNION (SELECT "t4".* FROM "t4")',
            // @codingStandardsIgnoreEnd
            $this->combine->getSqlString()
        );
    }

    public function testGetSqlStringWithModifier(): void
    {
        $this->combine
                ->union(new Select('t1'))
                ->union(new Select('t2'), 'ALL');

        self::assertEquals(
            '(SELECT "t1".* FROM "t1") UNION ALL (SELECT "t2".* FROM "t2")',
            $this->combine->getSqlString()
        );
    }

    public function testGetSqlStringFromArray(): void
    {
        $this->combine->combine([
            [new Select('t1')],
            [new Select('t2'), Combine::COMBINE_INTERSECT, 'ALL'],
            [new Select('t3'), Combine::COMBINE_EXCEPT],
        ]);

        self::assertEquals(
            '(SELECT "t1".* FROM "t1") INTERSECT ALL (SELECT "t2".* FROM "t2") EXCEPT (SELECT "t3".* FROM "t3")',
            $this->combine->getSqlString()
        );

        $this->combine = new Combine();
        $this->combine->combine([
            new Select('t1'),
            new Select('t2'),
            new Select('t3'),
        ]);

        self::assertEquals(
            '(SELECT "t1".* FROM "t1") UNION (SELECT "t2".* FROM "t2") UNION (SELECT "t3".* FROM "t3")',
            $this->combine->getSqlString()
        );
    }

    public function testGetSqlStringEmpty(): void
    {
        self::assertEmpty($this->combine->getSqlString());
    }

    public function testPrepareStatementWithModifier(): void
    {
        $select1 = new Select('t1');
        $select1->where(['x1' => 10]);

        $select2 = new Select('t2');
        $select2->where(['x2' => 20]);

        $this->combine->combine([
            $select1,
            $select2,
        ]);

        $adapter = $this->getMockAdapter();

        $statement = $this->combine->prepareStatement($adapter, new StatementContainer());
        self::assertInstanceOf(StatementContainerInterface::class, $statement);
        self::assertEquals(
            '(SELECT "t1".* FROM "t1" WHERE "x1" = ?) UNION (SELECT "t2".* FROM "t2" WHERE "x2" = ?)',
            $statement->getSql()
        );
    }

    public function testAlignColumns(): void
    {
        $select1 = new Select('t1');
        $select1->columns([
            'c0' => 'c0',
            'c1' => 'c1',
        ]);
        $select2 = new Select('t2');
        $select2->columns([
            'c1' => 'c1',
            'c2' => 'c2',
        ]);

        $this->combine
                ->union([$select1, $select2])
                ->alignColumns();

        // Verify first select has NULL for missing c2
        self::assertEquals(
            [
                'c0' => 'c0',
                'c1' => 'c1',
                'c2' => new Expression('NULL'),
            ],
            $select1->getRawState('columns')
        );

        // Verify second select has NULL for missing c0
        self::assertEquals(
            [
                'c0' => new Expression('NULL'),
                'c1' => 'c1',
                'c2' => 'c2',
            ],
            $select2->getRawState('columns')
        );
    }

    public function testGetRawState(): void
    {
        $select = new Select('t1');
        $this->combine->combine($select);
        self::assertSame(
            [
                'combine' => [
                    [
                        'select'   => $select,
                        'type'     => Combine::COMBINE_UNION,
                        'modifier' => '',
                    ],
                ],
                'columns' => [
                    '0' => '*',
                ],
            ],
            $this->combine->getRawState()
        );
    }

    public function testCombineWithArrayOfSelectAndModifier(): void
    {
        $this->combine->combine([
            [new Select('t1'), 'UNION', 'ALL'],
            [new Select('t2'), 'INTERSECT'],
        ]);

        self::assertEquals(
            '(SELECT "t1".* FROM "t1") INTERSECT (SELECT "t2".* FROM "t2")',
            $this->combine->getSqlString()
        );
    }

    public function testAlignColumnsAppendsNullExpressionsForMissingColumns(): void
    {
        $select1 = new Select('t1');
        $select1->columns(['a' => 'a']);

        $select2 = new Select('t2');
        $select2->columns(['a' => 'a', 'b' => 'b']);

        $this->combine->union([$select1, $select2])->alignColumns();

        $columns1 = $select1->getRawState('columns');
        self::assertArrayHasKey('b', $columns1);
        self::assertInstanceOf(Expression::class, $columns1['b']);
    }

    public function testConstructorWithSelectDelegatesToCombine(): void
    {
        $select  = new Select('foo');
        $combine = new Combine($select, Combine::COMBINE_EXCEPT, 'ALL');

        $rawState = $combine->getRawState();
        self::assertCount(1, $rawState['combine']);
        self::assertSame('except', $rawState['combine'][0]['type']);
        self::assertSame('ALL', $rawState['combine'][0]['modifier']);
    }

    public function testAlignColumnsReturnsEarlyWhenEmpty(): void
    {
        $combine = new Combine();
        $result  = $combine->alignColumns();

        self::assertSame($combine, $result);
    }

    protected function getMockAdapter(): Adapter|MockObject
    {
        $parameterContainer = new ParameterContainer();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('getParameterContainer')
            ->willReturn($parameterContainer);

        $setGetSqlFunction = function ($sql = null) use ($mockStatement) {
            static $sqlValue;
            if ($sql) {
                $sqlValue = $sql;
                return $mockStatement;
            }

            return $sqlValue;
        };
        $mockStatement->expects($this->any())->method('setSql')->willReturnCallback($setGetSqlFunction);
        $mockStatement->expects($this->any())->method('getSql')->willReturnCallback($setGetSqlFunction);

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);

        return $this->createMockAdapter($mockDriver);
    }
}
