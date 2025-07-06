<?php

namespace PhpDbTest\Sql\Platform\IbmDb2;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\IbmDb2 as IbmDb2Platform;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Platform\IbmDb2\SelectDecorator;
use PhpDb\Sql\Select;
use PhpDb\Sql\Where;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversMethod(\PhpDb\Sql\Platform\SqlServer\SelectDecorator::class, 'prepareStatement')]
#[CoversMethod(\PhpDb\Sql\Platform\SqlServer\SelectDecorator::class, 'processLimitOffset')]
#[CoversMethod(SelectDecorator::class, 'getSqlString')]
final class SelectDecoratorTest extends TestCase
{
    #[DataProvider('dataProvider')]
    #[TestDox('integration test: Testing SelectDecorator will use Select to produce properly IBM Db2
                           dialect prepared sql')]
    public function testPrepareStatement(
        Select $select,
        string $expectedPrepareSql,
        array $expectedParams,
        mixed $notUsed,
        bool $supportsLimitOffset
    ): void {
        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $driver->expects($this->any())->method('formatParameterName')->willReturn('?');

        // test
        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $driver,
                new IbmDb2Platform(),
            ])
            ->getMock();

        $parameterContainer = new ParameterContainer();
        $statement          = $this->getMockBuilder(StatementInterface::class)->getMock();

        $statement
            ->expects($this->any())
            ->method('getParameterContainer')
            ->willReturn($parameterContainer);
        $statement
            ->expects($this->once())
            ->method('setSql')
            ->with($expectedPrepareSql);

        $selectDecorator = new SelectDecorator();
        $selectDecorator->setSubject($select);
        $selectDecorator->setSupportsLimitOffset($supportsLimitOffset);
        $selectDecorator->prepareStatement($adapter, $statement);

        self::assertEquals($expectedParams, $parameterContainer->getNamedArray());
    }

    #[DataProvider('dataProvider')]
    #[TestDox('integration test: Testing SelectDecorator will use Select to produce properly Ibm DB2
                           dialect sql statements')]
    public function testGetSqlString(
        Select $select,
        mixed $ignored0,
        mixed $ignored1,
        string $expectedSql,
        bool $supportsLimitOffset
    ): void {
        $parameterContainer = new ParameterContainer();
        $statement          = $this->getMockBuilder(StatementInterface::class)->getMock();
        $statement
            ->expects($this->any())
            ->method('getParameterContainer')
            ->willReturn($parameterContainer);

        $selectDecorator = new SelectDecorator();
        $selectDecorator->setSubject($select);
        $selectDecorator->setSupportsLimitOffset($supportsLimitOffset);

        self::assertEquals($expectedSql, @$selectDecorator->getSqlString(new IbmDb2Platform()));
    }

    /**
     * Data provider for testGetSqlString
     */
    public static function dataProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $select0 = new Select();
        $select0->from(['x' => 'foo'])->limit(5);
        $expectedParams0     = ['limit' => 5, 'offset' => 0];
        $expectedPrepareSql0 = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN ? AND ?';
        $expectedSql0        = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN 0 AND 5';

        $select1 = new Select();
        $select1->from(['x' => 'foo'])->limit(5)->offset(10);
        $expectedParams1     = ['limit' => 15, 'offset' => 11];
        $expectedPrepareSql1 = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN ? AND ?';
        $expectedSql1        = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN 11 AND 15';

        $select2 = new Select();
        $select2->columns([new Expression('DISTINCT(id) as id')])->from(['x' => 'foo'])->limit(5)->offset(10);
        $expectedParams2     = ['limit' => 15, 'offset' => 11];
        $expectedPrepareSql2 = 'SELECT DISTINCT(id) as id FROM ( SELECT DISTINCT(id) as id, DENSE_RANK() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN ? AND ?';
        $expectedSql2        = 'SELECT DISTINCT(id) as id FROM ( SELECT DISTINCT(id) as id, DENSE_RANK() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN 11 AND 15';

        $select3 = new Select();
        $where3  = new Where();
        $where3->greaterThan('x.id', '10')->AND->lessThan('x.id', '31');
        $select3->from(['x' => 'foo'])->where($where3)->limit(5)->offset(10);
        $expectedParams3     = ['limit' => 15, 'offset' => 11, 'where1' => '10', 'where2' => '31'];
        $expectedPrepareSql3 = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" WHERE "x"."id" > ? AND "x"."id" < ? ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN ? AND ?';
        $expectedSql3        = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" WHERE "x"."id" > \'10\' AND "x"."id" < \'31\' ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN 11 AND 15';

        $select4 = new Select();
        $where4  = $where3;
        $select4->from(['x' => 'foo'])->where($where4)->limit(5);
        $expectedParams4     = ['limit' => 5, 'offset' => 0, 'where1' => 10, 'where2' => 31];
        $expectedPrepareSql4 = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" WHERE "x"."id" > ? AND "x"."id" < ? ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN ? AND ?';
        $expectedSql4        = 'SELECT * FROM ( SELECT "x".*, ROW_NUMBER() OVER () AS LAMINAS_DB_ROWNUM FROM "foo" "x" WHERE "x"."id" > \'10\' AND "x"."id" < \'31\' ) AS LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION WHERE LAMINAS_IBMDB2_SERVER_LIMIT_OFFSET_EMULATION.LAMINAS_DB_ROWNUM BETWEEN 0 AND 5';

        $select5 = new Select();
        $select5->from(['x' => 'foo'])->limit(5);
        $expectedParams5     = [];
        $expectedPrepareSql5 = 'SELECT "x".* FROM "foo" "x" LIMIT 5';
        $expectedSql5        = 'SELECT "x".* FROM "foo" "x" LIMIT 5';

        $select6 = new Select();
        $select6->columns([new Expression('DISTINCT(id) as id')])->from(['x' => 'foo'])->limit(5)->offset(10);
        $expectedParams6     = [];
        $expectedPrepareSql6 = 'SELECT DISTINCT(id) as id FROM "foo" "x" LIMIT 5 OFFSET 10';
        $expectedSql6        = 'SELECT DISTINCT(id) as id FROM "foo" "x" LIMIT 5 OFFSET 10';

        return [
            [$select0, $expectedPrepareSql0, $expectedParams0, $expectedSql0, false],
            [$select1, $expectedPrepareSql1, $expectedParams1, $expectedSql1, false],
            [$select2, $expectedPrepareSql2, $expectedParams2, $expectedSql2, false],
            [$select3, $expectedPrepareSql3, $expectedParams3, $expectedSql3, false],
            [$select4, $expectedPrepareSql4, $expectedParams4, $expectedSql4, false],
            [$select5, $expectedPrepareSql5, $expectedParams5, $expectedSql5, true],
            [$select6, $expectedPrepareSql6, $expectedParams6, $expectedSql6, true],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
