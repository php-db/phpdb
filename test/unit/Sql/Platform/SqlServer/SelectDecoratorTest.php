<?php

namespace PhpDbTest\Sql\Platform\SqlServer;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\SqlServer as SqlServerPlatform;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Platform\SqlServer\SelectDecorator;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SelectDecorator::class, 'prepareStatement')]
#[CoversMethod(SelectDecorator::class, 'processLimitOffset')]
#[CoversMethod(SelectDecorator::class, 'getSqlString')]
final class SelectDecoratorTest extends TestCase
{
    /**
     * @param array<string, mixed> $expectedParams
     */
    #[DataProvider('dataProvider')]
    #[TestDox('integration test: Testing SelectDecorator will use Select an internal state to prepare
                           a proper limit/offset sql statement')]
    public function testPrepareStatement(
        Select $select,
        string $expectedSql,
        array $expectedParams,
        mixed $notUsed,
        int $expectedFormatParamCount
    ): void {
        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $driver->expects($this->exactly($expectedFormatParamCount))->method('formatParameterName')
            ->willReturn('?');

        // test
        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                $driver,
                new SqlServerPlatform(),
            ])
            ->getMock();

        $parameterContainer = new ParameterContainer();
        $statement          = $this->getMockBuilder(StatementInterface::class)->getMock();
        $statement->expects($this->any())->method('getParameterContainer')
            ->willReturn($parameterContainer);

        $statement->expects($this->once())->method('setSql')->with($expectedSql);

        $selectDecorator = new SelectDecorator();
        $selectDecorator->setSubject($select);
        $selectDecorator->prepareStatement($adapter, $statement);

        self::assertEquals($expectedParams, $parameterContainer->getNamedArray());
    }

    #[DataProvider('dataProvider')]
    #[TestDox('integration test: Testing SelectDecorator will use Select an internal state to prepare
                           a proper limit/offset sql statement')]
    public function testGetSqlString(Select $select, mixed $ignored, mixed $alsoIgnored, string $expectedSql): void
    {
        $parameterContainer = new ParameterContainer();
        $statement          = $this->getMockBuilder(StatementInterface::class)->getMock();
        $statement->expects($this->any())->method('getParameterContainer')
            ->willReturn($parameterContainer);

        $selectDecorator = new SelectDecorator();
        $selectDecorator->setSubject($select);
        self::assertEquals($expectedSql, $selectDecorator->getSqlString(new SqlServerPlatform()));
    }

    /** @psalm-return array<array-key, array{0: Select, 1: string, 2: array<string, mixed>, 3: string, 4: int}> */
    public static function dataProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $select0 = new Select();
        $select0->from('foo')->columns(['bar', 'baz'])->order('bar')->limit(5)->offset(10);
        $expectedPrepareSql0       = 'SELECT [bar], [baz] FROM ( SELECT [foo].[bar] AS [bar], [foo].[baz] AS [baz], ROW_NUMBER() OVER (ORDER BY [bar] ASC) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN ?+1 AND ?+?';
        $expectedParams0           = ['offset' => 10, 'limit' => 5, 'offsetForSum' => 10];
        $expectedSql0              = 'SELECT [bar], [baz] FROM ( SELECT [foo].[bar] AS [bar], [foo].[baz] AS [baz], ROW_NUMBER() OVER (ORDER BY [bar] ASC) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN 10+1 AND 5+10';
        $expectedFormatParamCount0 = 3;

        $select1 = new Select();
        $select1->from('foo')->columns(['bar', 'bam' => 'baz'])->limit(5)->offset(10);
        $expectedPrepareSql1       = 'SELECT [bar], [bam] FROM ( SELECT [foo].[bar] AS [bar], [foo].[baz] AS [bam], ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN ?+1 AND ?+?';
        $expectedParams1           = ['offset' => 10, 'limit' => 5, 'offsetForSum' => 10];
        $expectedSql1              = 'SELECT [bar], [bam] FROM ( SELECT [foo].[bar] AS [bar], [foo].[baz] AS [bam], ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN 10+1 AND 5+10';
        $expectedFormatParamCount1 = 3;

        $select2 = new Select();
        $select2->from('foo')->order('bar')->limit(5)->offset(10);
        $expectedPrepareSql2       = 'SELECT * FROM ( SELECT [foo].*, ROW_NUMBER() OVER (ORDER BY [bar] ASC) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN ?+1 AND ?+?';
        $expectedParams2           = ['offset' => 10, 'limit' => 5, 'offsetForSum' => 10];
        $expectedSql2              = 'SELECT * FROM ( SELECT [foo].*, ROW_NUMBER() OVER (ORDER BY [bar] ASC) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN 10+1 AND 5+10';
        $expectedFormatParamCount2 = 3;

        $select3 = new Select();
        $select3->from('foo');
        $expectedPrepareSql3       = 'SELECT [foo].* FROM [foo]';
        $expectedParams3           = [];
        $expectedSql3              = 'SELECT [foo].* FROM [foo]';
        $expectedFormatParamCount3 = 0;

        $select4 = new Select();
        $select4->from('foo')->columns([new Expression('DISTINCT(bar) as bar')])->limit(5)->offset(10);
        $expectedPrepareSql4       = 'SELECT DISTINCT(bar) as bar FROM ( SELECT DISTINCT(bar) as bar, ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN ?+1 AND ?+?';
        $expectedParams4           = ['offset' => 10, 'limit' => 5, 'offsetForSum' => 10];
        $expectedSql4              = 'SELECT DISTINCT(bar) as bar FROM ( SELECT DISTINCT(bar) as bar, ROW_NUMBER() OVER (ORDER BY (SELECT 1)) AS [__LAMINAS_ROW_NUMBER] FROM [foo] ) AS [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION] WHERE [LAMINAS_SQL_SERVER_LIMIT_OFFSET_EMULATION].[__LAMINAS_ROW_NUMBER] BETWEEN 10+1 AND 5+10';
        $expectedFormatParamCount4 = 3;

        return [
            [$select0, $expectedPrepareSql0, $expectedParams0, $expectedSql0, $expectedFormatParamCount0],
            [$select1, $expectedPrepareSql1, $expectedParams1, $expectedSql1, $expectedFormatParamCount1],
            [$select2, $expectedPrepareSql2, $expectedParams2, $expectedSql2, $expectedFormatParamCount2],
            [$select3, $expectedPrepareSql3, $expectedParams3, $expectedSql3, $expectedFormatParamCount3],
            [$select4, $expectedPrepareSql4, $expectedParams4, $expectedSql4, $expectedFormatParamCount4],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
