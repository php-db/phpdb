<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function is_array;
use function is_string;

/**
 * @method Select select(string|array|null $sqlString)
 * @method Update update(TableIdentifier|null|string $sqlString)
 * @method Delete delete(TableIdentifier|null|string $sqlString)
 * @method Insert insert(TableIdentifier|null|string $sqlString)
 * @method CreateTable createTable(null|string|TableIdentifier $sqlString)
 * @method Column createColumn(null|string $sqlString)
 */
class SqlFunctionalTest extends TestCase
{
    protected static function dataProviderCommonProcessMethods(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'Select::processOffset()'      => [
                'sqlObject' => self::select('foo')->offset(10),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'SELECT "foo".* FROM "foo" OFFSET \'10\'',
                        'prepare'    => 'SELECT "foo".* FROM "foo" OFFSET ?',
                        'parameters' => ['offset' => 10],
                    ],
                ],
            ],
            'Select::processLimit()'       => [
                'sqlObject' => self::select('foo')->limit(10),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'SELECT "foo".* FROM "foo" LIMIT \'10\'',
                        'prepare'    => 'SELECT "foo".* FROM "foo" LIMIT ?',
                        'parameters' => ['limit' => 10],
                    ],
                ],
            ],
            'Select::processLimitOffset()' => [
                'sqlObject' => self::select('foo')->limit(10)->offset(5),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'SELECT "foo".* FROM "foo" LIMIT \'10\' OFFSET \'5\'',
                        'prepare'    => 'SELECT "foo".* FROM "foo" LIMIT ? OFFSET ?',
                        'parameters' => ['limit' => 10, 'offset' => 5],
                    ],
                ],
            ],
            // Github issue https://github.com/zendframework/zend-db/issues/98
            'Select::processJoinNoJoinedColumns()' => [
                'sqlObject' => self::select('my_table')
                    ->join(
                        'joined_table2',
                        'my_table.id = joined_table2.id',
                        []
                    )
                    ->join(
                        'joined_table3',
                        'my_table.id = joined_table3.id',
                        [Select::SQL_STAR]
                    )
                    ->columns([
                        'my_table_column',
                        'aliased_column' => new Expression('NOW()'),
                    ]),
                'expected'  => [
                    'sql92' => [
                        'string' => 'SELECT "my_table"."my_table_column" AS "my_table_column", NOW() AS "aliased_column", "joined_table3".* FROM "my_table" INNER JOIN "joined_table2" ON "my_table"."id" = "joined_table2"."id" INNER JOIN "joined_table3" ON "my_table"."id" = "joined_table3"."id"',
                    ],
                ],
            ],
            'Select::processJoin()'                => [
                'sqlObject' => self::select('a')
                    ->join(['b' => self::select('c')->where(['cc' => 10])], 'd=e')->where(['x' => 20]),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'SELECT "a".*, "b".* FROM "a" INNER JOIN (SELECT "c".* FROM "c" WHERE "cc" = \'10\') AS "b" ON "d"="e" WHERE "x" = \'20\'',
                        'prepare'    => 'SELECT "a".*, "b".* FROM "a" INNER JOIN (SELECT "c".* FROM "c" WHERE "cc" = ?) AS "b" ON "d"="e" WHERE "x" = ?',
                        'parameters' => ['subselect1where1' => 10, 'where1' => 20],
                    ],
                ],
            ],
            'Ddl::CreateTable::processColumns()'   => [
                'sqlObject' => self::createTable('foo')
                    ->addColumn(self::createColumn('col1')
                        ->setOption('identity', true)
                        ->setOption('comment', 'Comment1'))
                    ->addColumn(self::createColumn('col2')
                        ->setOption('identity', true)
                        ->setOption('comment', 'Comment2')),
                'expected'  => [
                    'sql92' => "CREATE TABLE \"foo\" ( \n    \"col1\" INTEGER NOT NULL,\n    \"col2\" INTEGER NOT NULL \n)",
                ],
            ],
            'Ddl::CreateTable::processTable()'     => [
                'sqlObject' => self::createTable('foo')->setTemporary(true),
                'expected'  => [
                    'sql92' => "CREATE TEMPORARY TABLE \"foo\" ( \n)",
                ],
            ],
            'Select::processSubSelect()'           => [
                'sqlObject' => self::select([
                    'a' => self::select([
                        'b' => self::select('c')->where(['cc' => 'CC']),
                    ])
                        ->where(['bb' => 'BB']),
                ])
                    ->where(['aa' => 'AA']),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'SELECT "a".* FROM (SELECT "b".* FROM (SELECT "c".* FROM "c" WHERE "cc" = \'CC\') AS "b" WHERE "bb" = \'BB\') AS "a" WHERE "aa" = \'AA\'',
                        'prepare'    => 'SELECT "a".* FROM (SELECT "b".* FROM (SELECT "c".* FROM "c" WHERE "cc" = ?) AS "b" WHERE "bb" = ?) AS "a" WHERE "aa" = ?',
                        'parameters' => ['subselect2where1' => 'CC', 'subselect1where1' => 'BB', 'where1' => 'AA'],
                    ],
                ],
            ],
            'Delete::processSubSelect()'           => [
                'sqlObject' => self::delete('foo')->where(['x' => self::select('foo')->where(['x' => 'y'])]),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'DELETE FROM "foo" WHERE "x" = (SELECT "foo".* FROM "foo" WHERE "x" = \'y\')',
                        'prepare'    => 'DELETE FROM "foo" WHERE "x" = (SELECT "foo".* FROM "foo" WHERE "x" = ?)',
                        'parameters' => ['subselect1where1' => 'y'],
                    ],
                ],
            ],
            'Update::processSubSelect()'           => [
                'sqlObject' => self::update('foo')->set(['x' => self::select('foo')]),
                'expected'  => [
                    'sql92' => 'UPDATE "foo" SET "x" = (SELECT "foo".* FROM "foo")',
                ],
            ],
            'Insert::processSubSelect()'           => [
                'sqlObject' => self::insert('foo')->select(self::select('foo')->where(['x' => 'y'])),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'INSERT INTO "foo"  SELECT "foo".* FROM "foo" WHERE "x" = \'y\'',
                        'prepare'    => 'INSERT INTO "foo"  SELECT "foo".* FROM "foo" WHERE "x" = ?',
                        'parameters' => ['subselect1where1' => 'y'],
                    ],
                ],
            ],
            'Update::processExpression()'          => [
                'sqlObject' => self::update('foo')->set(
                    ['x' => new Sql\Expression('?', [self::select('foo')->where(['x' => 'y'])])]
                ),
                'expected'  => [
                    'sql92' => [
                        'string'     => 'UPDATE "foo" SET "x" = (SELECT "foo".* FROM "foo" WHERE "x" = \'y\')',
                        'prepare'    => 'UPDATE "foo" SET "x" = (SELECT "foo".* FROM "foo" WHERE "x" = ?)',
                        'parameters' => ['subselect1where1' => 'y'],
                    ],
                ],
            ],
            'Update::processJoins()'               => [
                'sqlObject' => self::update('foo')->set(['x' => 'y'])->where(['xx' => 'yy'])->join(
                    'bar',
                    'bar.barId = foo.barId'
                ),
                'expected'  => [
                    'sql92' => [
                        'string' => 'UPDATE "foo" INNER JOIN "bar" ON "bar"."barId" = "foo"."barId" SET "x" = \'y\' WHERE "xx" = \'yy\'',
                    ],
                ],
            ],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    protected static function dataProviderDecorators(): array
    {
        return [
            'RootDecorators::Select' => [
                'sqlObject' => self::select('foo')->where(['x' => self::select('bar')]),
                'expected'  => [
                    'sql92' => [
                        'decorators' => [
                            Select::class => new TestAsset\SelectDecorator(),
                        ],
                        'string'     => 'SELECT "foo".* FROM "foo" WHERE "x" = (SELECT "bar".* FROM "bar")',
                    ],
                ],
            ],
            // phpcs:disable Generic.Files.LineLength.TooLong
            /* TODO - should be implemented
            'RootDecorators::Insert' => array(
                'sqlObject' => self::insert('foo')->select(self::select()),
                'expected'  => array(
                    'sql92'     => array(
                        'decorators' => array(
                            'PhpDb\Sql\Insert' => new TestAsset\InsertDecorator, // Decorator for root sqlObject
                            'PhpDb\Sql\Select' => array('PhpDb\Sql\Platform\Mysql\SelectDecorator', '{=SELECT_Sql92=}')
                        ),
                        'string' => 'INSERT INTO "foo"  {=SELECT_Sql92=}',
                    ),
                ),
            ),*/
            // phpcs:enable Generic.Files.LineLength.TooLong
        ];
    }

    public static function dataProvider(): array
    {
        $data = array_merge(
            self::dataProviderCommonProcessMethods(),
            self::dataProviderDecorators()
        );

        $res = [];
        foreach ($data as $index => $test) {
            self::assertIsArray($test);
            $testExpected = $test['expected'] ?? [];
            self::assertIsArray($testExpected);
            /** @psalm-suppress MixedAssignment */
            foreach ($testExpected as $platform => $expected) {
                $res[$index . '->' . $platform] = [
                    'sqlObject' => $test['sqlObject'],
                    'platform'  => $platform,
                    'expected'  => $expected,
                ];
            }
        }

        return $res;
    }

    #[DataProvider('dataProvider')]
    public function test(PreparableSqlInterface|SqlInterface $sqlObject, string $platform, string|array $expected): void
    {
        $sql = new Sql\Sql($this->resolveAdapter($platform));

        if (is_array($expected) && isset($expected['decorators'])) {
            /** @var PlatformDecoratorInterface|array $decorator */
            foreach ($expected['decorators'] as $type => $decorator) {
                self::assertIsString($type);
                $decorator = $this->resolveDecorator($decorator);
                $this->assertInstanceOf(PlatformDecoratorInterface::class, $decorator);

                $platform = $sql->getSqlPlatform();
                $this->assertNotNull($platform);
                $platform->setTypeDecorator($type, $decorator);
            }
        }

        $expectedString = is_string($expected) ? $expected : (string) $expected['string'];
        if ($expectedString !== '') {
            self::assertInstanceOf(SqlInterface::class, $sqlObject);
            $actual = $sql->buildSqlString($sqlObject);
            self::assertEquals($expectedString, $actual, 'getSqlString()');
        }

        if (is_array($expected) && isset($expected['prepare'])) {
            self::assertInstanceOf(PreparableSqlInterface::class, $sqlObject);
            /** @var StatementInterface|StatementContainer $actual */
            $actual = $sql->prepareStatementForSqlObject($sqlObject);
            self::assertEquals($expected['prepare'], $actual->getSql(), 'prepareStatement()');
            if (isset($expected['parameters'])) {
                $parametersContainer = $actual->getParameterContainer();
                self::assertInstanceOf(ParameterContainer::class, $parametersContainer);
                $actual = $parametersContainer->getNamedArray();
                self::assertSame($expected['parameters'], $actual, 'parameterContainer()');
            }
        }
    }

    protected function resolveDecorator(
        PlatformDecoratorInterface|array $decorator
    ): PlatformDecoratorInterface|MockObject|null {
        if (is_array($decorator)) {
            /** @var class-string $classString */
            $classString   = $decorator[0];
            $decoratorMock = $this->getMockBuilder($classString)
                ->onlyMethods(['buildSqlString'])
                ->setConstructorArgs([null])
                ->getMock();
            $decoratorMock->expects($this->any())->method('buildSqlString')->willReturn($decorator[1]);
            return $decoratorMock;
        }
        return $decorator;
    }

    protected function resolveAdapter(string $platform): Adapter\Adapter
    {
        // Only sql92 platform is supported after abstraction
        $platform = match ($platform) {
            'sql92' => new TestAsset\TrustingSql92Platform(),
            default => new TestAsset\TrustingSql92Platform(), // Default to sql92 for any other value
        };

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturn('?');
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturnCallback(function (): MockObject {
                $container = new Adapter\StatementContainer();
                // Create a mock statement that delegates to the container for SQL/params
                $mockStatement = $this->createMock(StatementInterface::class);
                $mockStatement->expects($this->any())
                    ->method('setSql')
                    ->willReturnCallback(function ($sql) use ($container, $mockStatement): MockObject {
                        $container->setSql($sql);
                        return $mockStatement;
                    });
                $mockStatement->expects($this->any())
                    ->method('getSql')
                    ->willReturnCallback(fn(): ?string => $container->getSql());
                $mockStatement->expects($this->any())
                    ->method('setParameterContainer')
                    ->willReturnCallback(
                        function (ParameterContainer $params) use ($container, $mockStatement): MockObject {
                            $container->setParameterContainer($params);
                            return $mockStatement;
                        }
                    );
                $mockStatement->expects($this->any())
                    ->method('getParameterContainer')
                    ->willReturnCallback(fn(): ?ParameterContainer => $container->getParameterContainer());
                return $mockStatement;
            });

        return new Adapter\Adapter($mockDriver, $platform, new TestAsset\TemporaryResultSet());
    }

    protected static function select(string|array|null $sqlString): Sql\Select
    {
        return new Sql\Select($sqlString);
    }

    protected static function delete(string|TableIdentifier|null $sqlString): Sql\Delete
    {
        return new Sql\Delete($sqlString);
    }

    protected static function update(string|TableIdentifier|null $sqlString): Sql\Update
    {
        return new Sql\Update($sqlString);
    }

    protected static function insert(string|TableIdentifier|null $sqlString): Sql\Insert
    {
        return new Sql\Insert($sqlString);
    }

    protected static function createTable(string|TableIdentifier $sqlString): Sql\Ddl\CreateTable
    {
        return new Sql\Ddl\CreateTable($sqlString);
    }

    protected static function createColumn(?string $sqlString): Sql\Ddl\Column\Column
    {
        return new Sql\Ddl\Column\Column($sqlString);
    }
}
