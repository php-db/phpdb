<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Source;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\Exception\RuntimeException;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintKeyObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\TriggerObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\Metadata\Source\AbstractSource;
use PhpDbTest\Metadata\Source\TestAsset\IncompleteSource;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

use function sprintf;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(AbstractSource::class, '__construct')]
#[CoversMethod(AbstractSource::class, 'getSchemas')]
#[CoversMethod(AbstractSource::class, 'getTableNames')]
#[CoversMethod(AbstractSource::class, 'getTables')]
#[CoversMethod(AbstractSource::class, 'getTable')]
#[CoversMethod(AbstractSource::class, 'getViewNames')]
#[CoversMethod(AbstractSource::class, 'getViews')]
#[CoversMethod(AbstractSource::class, 'getView')]
#[CoversMethod(AbstractSource::class, 'getColumnNames')]
#[CoversMethod(AbstractSource::class, 'getColumns')]
#[CoversMethod(AbstractSource::class, 'getColumn')]
#[CoversMethod(AbstractSource::class, 'getConstraints')]
#[CoversMethod(AbstractSource::class, 'getConstraint')]
#[CoversMethod(AbstractSource::class, 'getConstraintKeys')]
#[CoversMethod(AbstractSource::class, 'getTriggerNames')]
#[CoversMethod(AbstractSource::class, 'getTriggers')]
#[CoversMethod(AbstractSource::class, 'getTrigger')]
#[CoversMethod(AbstractSource::class, 'prepareDataHierarchy')]
#[CoversMethod(AbstractSource::class, 'loadTableNameData')]
#[CoversMethod(AbstractSource::class, 'loadColumnData')]
#[CoversMethod(AbstractSource::class, 'loadConstraintData')]
#[CoversMethod(AbstractSource::class, 'loadConstraintDataKeys')]
#[CoversMethod(AbstractSource::class, 'loadConstraintReferences')]
#[CoversMethod(AbstractSource::class, 'loadTriggerData')]
#[Group('unit')]
final class AbstractSourceTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    #[Test]
    public function constructorWithNullSchemaUsesDefaultConstant(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([AdapterInterface::class, SchemaAwareInterface::class]);
        $adapter->method('getCurrentSchema')->willReturn(false);

        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$adapter])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $refProp = new ReflectionProperty($source, 'defaultSchema');

        // Verify default constant is used when adapter returns false
        static::assertSame(AbstractSource::DEFAULT_SCHEMA, $refProp->getValue($source));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function constructorWithSchemaFromAdapter(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([AdapterInterface::class, SchemaAwareInterface::class]);
        $adapter->method('getCurrentSchema')->willReturn('my_schema');

        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$adapter])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $refProp = new ReflectionProperty($source, 'defaultSchema');

        // Verify schema is retrieved from adapter
        static::assertSame('my_schema', $refProp->getValue($source));
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getColumn(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => [
                        'username' => [
                            'ordinal_position'         => 2,
                            'column_default'           => '',
                            'is_nullable'              => false,
                            'data_type'                => 'VARCHAR',
                            'character_maximum_length' => 255,
                            'character_octet_length'   => 1024,
                            'numeric_precision'        => null,
                            'numeric_scale'            => null,
                            'numeric_unsigned'         => null,
                            'erratas'                  => ['collation' => 'utf8_general_ci'],
                        ],
                    ],
                ],
            ],
        ]);

        $column = $this->abstractSourceMock->getColumn('username', 'users', 'public');
        // Verify getColumn returns ColumnObject with all properties

        static::assertInstanceOf(ColumnObject::class, $column);
        static::assertSame('username', $column->getName());
        static::assertSame('users', $column->getTableName());
        static::assertSame('public', $column->getSchemaName());
        static::assertSame(2, $column->getOrdinalPosition());
        static::assertSame('', $column->getColumnDefault());
        static::assertFalse($column->getIsNullable());
        static::assertSame('VARCHAR', $column->getDataType());
        static::assertSame(255, $column->getCharacterMaximumLength());
        static::assertSame(1024, $column->getCharacterOctetLength());
        static::assertNull($column->getNumericPrecision());
        static::assertNull($column->getNumericScale());
        static::assertNull($column->getNumericUnsigned());
        static::assertSame('utf8_general_ci', $column->getErrata('collation'));
    }

    /**
     * Column Methods
     *
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getColumnNames(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => [
                        'id'       => [],
                        'username' => [],
                        'email'    => [],
                    ],
                ],
            ],
        ]);

        $columnNames = $this->abstractSourceMock->getColumnNames('users', 'public');
        // Verify getColumnNames returns array of column names

        static::assertSame(['id', 'username', 'email'], $columnNames);
    }

    #[Test]
    public function getColumnNamesThrowsWhenLoadColumnDataDoesNotPopulate(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([
            AdapterInterface::class,
            SchemaAwareInterface::class,
        ]);
        $adapter->method('getCurrentSchema')->willReturn('public');

        $source = new IncompleteSource($adapter);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNKNOWN_TABLE, 'nonexistent'));
        $source->getColumnNames('nonexistent', 'public');
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getColumnNamesUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'columns' => [
                'def' => [
                    'users' => [
                        'id'   => [],
                        'name' => [],
                    ],
                ],
            ],
        ]);

        $names = $this->abstractSourceMock->getColumnNames('users', null);

        static::assertSame(['id', 'name'], $names);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getColumns(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => [
                        'id' => [
                            'ordinal_position'         => 1,
                            'column_default'           => null,
                            'is_nullable'              => false,
                            'data_type'                => 'INT',
                            'character_maximum_length' => null,
                            'character_octet_length'   => null,
                            'numeric_precision'        => 10,
                            'numeric_scale'            => 0,
                            'numeric_unsigned'         => true,
                            'erratas'                  => [],
                        ],
                    ],
                ],
            ],
        ]);

        $columns = $this->abstractSourceMock->getColumns('users', 'public');
        // Verify getColumns returns array of ColumnObject instances

        static::assertCount(1, $columns);
        static::assertInstanceOf(ColumnObject::class, $columns[0]);
        static::assertSame('id', $columns[0]->getName());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getColumnsUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'columns' => [
                'def' => [
                    'users' => [
                        'id' => [
                            'ordinal_position'         => 1,
                            'column_default'           => null,
                            'is_nullable'              => false,
                            'data_type'                => 'INT',
                            'character_maximum_length' => null,
                            'character_octet_length'   => null,
                            'numeric_precision'        => null,
                            'numeric_scale'            => null,
                            'numeric_unsigned'         => null,
                            'erratas'                  => [],
                        ],
                    ],
                ],
            ],
        ]);

        $columns = $this->abstractSourceMock->getColumns('users', null);

        static::assertCount(1, $columns);
        static::assertInstanceOf(ColumnObject::class, $columns[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getColumnThrowsExceptionForNonExistentColumn(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => [],
                ],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNKNOWN_COLUMN);

        $this->abstractSourceMock->getColumn('non_existent', 'users', 'public');

        // Verify exception is thrown for non-existent column
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getColumnUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'columns' => [
                'def' => [
                    'users' => [
                        'id' => [
                            'ordinal_position'         => 1,
                            'column_default'           => null,
                            'is_nullable'              => false,
                            'data_type'                => 'INT',
                            'character_maximum_length' => null,
                            'character_octet_length'   => null,
                            'numeric_precision'        => null,
                            'numeric_scale'            => null,
                            'numeric_unsigned'         => null,
                            'erratas'                  => [],
                        ],
                    ],
                ],
            ],
        ]);

        $column = $this->abstractSourceMock->getColumn('id', 'users', null);

        static::assertInstanceOf(ColumnObject::class, $column);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getConstraint(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => [
                    'orders' => [
                        'fk_orders_user' => [
                            'constraint_type'         => 'FOREIGN KEY',
                            'columns'                 => ['user_id'],
                            'referenced_table_schema' => 'public',
                            'referenced_table_name'   => 'users',
                            'referenced_columns'      => ['id'],
                            'match_option'            => 'SIMPLE',
                            'update_rule'             => 'CASCADE',
                            'delete_rule'             => 'RESTRICT',
                        ],
                    ],
                ],
            ],
        ]);

        $constraint = $this->abstractSourceMock->getConstraint('fk_orders_user', 'orders', 'public');
        // Verify getConstraint returns ConstraintObject with all properties

        static::assertInstanceOf(ConstraintObject::class, $constraint);
        static::assertSame('fk_orders_user', $constraint->getName());
        static::assertSame('orders', $constraint->getTableName());
        static::assertSame('public', $constraint->getSchemaName());
        static::assertSame('FOREIGN KEY', $constraint->getType());
        static::assertSame(['user_id'], $constraint->getColumns());
        static::assertSame('public', $constraint->getReferencedTableSchema());
        static::assertSame('users', $constraint->getReferencedTableName());
        static::assertSame(['id'], $constraint->getReferencedColumns());
        static::assertSame('SIMPLE', $constraint->getMatchOption());
        static::assertSame('CASCADE', $constraint->getUpdateRule());
        static::assertSame('RESTRICT', $constraint->getDeleteRule());
    }

    /**
     * Constraint Key Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintKeys(): void
    {
        // internal data
        $data = [
            'constraint_references' => [
                'foo_schema' => [
                    [
                        'constraint_name'        => 'bam_constraint',
                        'update_rule'            => 'UP',
                        'delete_rule'            => 'DOWN',
                        'referenced_table_name'  => 'another_table',
                        'referenced_column_name' => 'another_column',
                    ],
                ],
            ],
            'constraint_keys'       => [
                'foo_schema' => [
                    [
                        'table_name'       => 'bar_table',
                        'constraint_name'  => 'bam_constraint',
                        'column_name'      => 'a',
                        'ordinal_position' => 1,
                    ],
                ],
            ],
        ];

        $this->setMockData($data);
        // Verify getConstraintKeys returns ConstraintKeyObject with references
        $constraints = $this->abstractSourceMock->getConstraintKeys('bam_constraint', 'bar_table', 'foo_schema');
        static::assertCount(1, $constraints);

        $constraintKeyObj = $constraints[0];
        static::assertInstanceOf(ConstraintKeyObject::class, $constraintKeyObj);

        // check value object is mapped correctly
        static::assertEquals('a', $constraintKeyObj->getColumnName());
        // Verify value object is mapped correctly
        static::assertEquals(1, $constraintKeyObj->getOrdinalPosition());
        static::assertEquals('another_table', $constraintKeyObj->getReferencedTableName());
        static::assertEquals('another_column', $constraintKeyObj->getReferencedColumnName());
        static::assertEquals('UP', $constraintKeyObj->getForeignKeyUpdateRule());
        static::assertEquals('DOWN', $constraintKeyObj->getForeignKeyDeleteRule());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintKeysUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'constraint_references' => [
                'def' => [],
            ],
            'constraint_keys'       => [
                'def' => [
                    [
                        'table_name'       => 'users',
                        'constraint_name'  => 'pk',
                        'column_name'      => 'id',
                        'ordinal_position' => 1,
                    ],
                ],
            ],
        ]);

        $keys = $this->abstractSourceMock->getConstraintKeys('pk', 'users', null);

        static::assertCount(1, $keys);
        static::assertInstanceOf(ConstraintKeyObject::class, $keys[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintKeysWithMultipleKeys(): void
    {
        $data = [
            'constraint_references' => [
                'public' => [
                    [
                        'constraint_name'        => 'fk_composite',
                        'update_rule'            => 'CASCADE',
                        'delete_rule'            => 'RESTRICT',
                        'referenced_table_name'  => 'ref_table',
                        'referenced_column_name' => 'ref_col',
                    ],
                ],
            ],
            'constraint_keys'       => [
                'public' => [
                    [
                        'table_name'       => 'my_table',
                        'constraint_name'  => 'fk_composite',
                        'column_name'      => 'col1',
                        'ordinal_position' => 1,
                    ],
                    [
                        'table_name'       => 'my_table',
                        'constraint_name'  => 'fk_composite',
                        'column_name'      => 'col2',
                        'ordinal_position' => 2,
                    ],
                ],
            ],
        ];

        $this->setMockData($data);
        // Verify composite constraint keys are returned in order
        $keys = $this->abstractSourceMock->getConstraintKeys('fk_composite', 'my_table', 'public');

        static::assertCount(2, $keys);
        static::assertSame('col1', $keys[0]->getColumnName());
        static::assertSame('col2', $keys[1]->getColumnName());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintKeysWithoutReferences(): void
    {
        $data = [
            'constraint_references' => [
                'public' => [],
            ],
            'constraint_keys'       => [
                'public' => [
                    [
                        'table_name'       => 'users',
                        'constraint_name'  => 'pk_users',
                        'column_name'      => 'id',
                        'ordinal_position' => 1,
                    ],
                ],
            ],
        ];

        $this->setMockData($data);
        // Verify constraint keys without references have null references
        $keys = $this->abstractSourceMock->getConstraintKeys('pk_users', 'users', 'public');

        static::assertCount(1, $keys);
        static::assertSame('id', $keys[0]->getColumnName());
        static::assertNull($keys[0]->getReferencedTableName());
    }

    /**
     * Constraint Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraints(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => [
                    'users' => [
                        'pk_users'       => [
                            'constraint_type' => 'PRIMARY KEY',
                            'columns'         => ['id'],
                        ],
                        'uq_users_email' => [
                            'constraint_type' => 'UNIQUE',
                            'columns'         => ['email'],
                        ],
                    ],
                ],
            ],
        ]);

        $constraints = $this->abstractSourceMock->getConstraints('users', 'public');
        // Verify getConstraints returns array of ConstraintObject instances

        static::assertCount(2, $constraints);
        static::assertInstanceOf(ConstraintObject::class, $constraints[0]);
        static::assertInstanceOf(ConstraintObject::class, $constraints[1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintsUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'constraints' => [
                'def' => [
                    'users' => [
                        'pk' => [
                            'constraint_type' => 'PRIMARY KEY',
                            'columns'         => ['id'],
                        ],
                    ],
                ],
            ],
        ]);

        $constraints = $this->abstractSourceMock->getConstraints('users', null);

        static::assertCount(1, $constraints);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintThrowsExceptionForNonExistent(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => [
                    'users' => [],
                ],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNKNOWN_CONSTRAINT);

        $this->abstractSourceMock->getConstraint('non_existent', 'users', 'public');

        // Verify exception is thrown for non-existent constraint
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getConstraintUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'constraints' => [
                'def' => [
                    'users' => [
                        'pk' => [
                            'constraint_type' => 'PRIMARY KEY',
                            'columns'         => ['id'],
                        ],
                    ],
                ],
            ],
        ]);

        $constraint = $this->abstractSourceMock->getConstraint('pk', 'users', null);

        static::assertInstanceOf(ConstraintObject::class, $constraint);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getConstraintWithCheckClause(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => [
                    'users' => [
                        'chk_age' => [
                            'constraint_type' => 'CHECK',
                            'check_clause'    => 'age >= 18',
                        ],
                    ],
                ],
            ],
        ]);

        $constraint = $this->abstractSourceMock->getConstraint('chk_age', 'users', 'public');
        // Verify getConstraint returns constraint with check clause

        static::assertSame('CHECK', $constraint->getType());
        static::assertSame('age >= 18', $constraint->getCheckClause());
    }

    /**
     * Schema Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getSchemasCallsLoadSchemaData(): void
    {
        $this->abstractSourceMock->expects($this->once())->method('loadSchemaData');

        $this->setMockData(['schemas' => ['schema1', 'schema2']]);

        // Verify getSchemas loads and returns schema list
        $schemas = $this->abstractSourceMock->getSchemas();
        static::assertSame(['schema1', 'schema2'], $schemas);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getTableForBaseTable(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users' => ['table_type' => 'BASE TABLE'],
                ],
            ],
            'columns'     => [
                'public' => [
                    'users' => [],
                ],
            ],
            'constraints' => [
                'public' => [
                    'users' => [],
                ],
            ],
        ]);

        // Verify getTable returns TableObject for base table
        $table = $this->abstractSourceMock->getTable('users', 'public');

        static::assertInstanceOf(TableObject::class, $table);
        static::assertSame('users', $table->getName());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getTableForView(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'user_summary' => [
                        'table_type'      => 'VIEW',
                        'view_definition' => 'SELECT id, name FROM users',
                        'check_option'    => 'CASCADED',
                        'is_updatable'    => false,
                    ],
                ],
            ],
            'columns'     => [
                'public' => [
                    'user_summary' => [],
                ],
            ],
            'constraints' => [
                'public' => [
                    'user_summary' => [],
                ],
            ],
        ]);

        $view = $this->abstractSourceMock->getTable('user_summary', 'public');
        // Verify getTable returns ViewObject for view type

        static::assertInstanceOf(ViewObject::class, $view);
        static::assertSame('user_summary', $view->getName());
        static::assertSame('SELECT id, name FROM users', $view->getViewDefinition());
        static::assertSame('CASCADED', $view->getCheckOption());
        static::assertFalse($view->getIsUpdatable());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableNamesExcludesViewsByDefault(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users'        => ['table_type' => 'BASE TABLE'],
                    'user_summary' => ['table_type' => 'VIEW'],
                    'orders'       => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        // Verify views are excluded by default
        $tableNames = $this->abstractSourceMock->getTableNames('public');

        static::assertSame(['users', 'orders'], $tableNames);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableNamesIncludesViewsWhenRequested(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users'        => ['table_type' => 'BASE TABLE'],
                    'user_summary' => ['table_type' => 'VIEW'],
                ],
            ],
        ]);

        // Verify views are included when flag is true
        $tableNames = $this->abstractSourceMock->getTableNames('public', true);

        static::assertSame(['users', 'user_summary'], $tableNames);
    }

    /**
     * Table Name Methods
     *
     * @throws ReflectionException
     * @throws ReflectionException
     */
    #[Test]
    public function getTableNamesWithNullSchemaUsesDefault(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'default_schema');

        $this->setMockData([
            'table_names' => [
                'default_schema' => [
                    'users'  => ['table_type' => 'BASE TABLE'],
                    'orders' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        // Verify default schema is used when none provided
        $tableNames = $this->abstractSourceMock->getTableNames();

        static::assertSame(['users', 'orders'], $tableNames);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableNamesWithSpecificSchema(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'products' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        // Verify table names for specific schema
        $tableNames = $this->abstractSourceMock->getTableNames('public');

        static::assertSame(['products'], $tableNames);
    }

    /**
     * Table Object Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getTables(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users'  => ['table_type' => 'BASE TABLE'],
                    'orders' => ['table_type' => 'BASE TABLE'],
                ],
            ],
            'columns'     => [
                'public' => [
                    'users'  => [],
                    'orders' => [],
                ],
            ],
            'constraints' => [
                'public' => [
                    'users'  => [],
                    'orders' => [],
                ],
            ],
        ]);

        // Verify getTables returns array of TableObject instances
        $tables = $this->abstractSourceMock->getTables('public');

        static::assertCount(2, $tables);
        static::assertInstanceOf(TableObject::class, $tables[0]);
        static::assertInstanceOf(TableObject::class, $tables[1]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTablesUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'table_names' => [
                'def' => [
                    'users' => ['table_type' => 'BASE TABLE'],
                ],
            ],
            'columns'     => [
                'def' => [
                    'users' => [],
                ],
            ],
            'constraints' => [
                'def' => [
                    'users' => [],
                ],
            ],
        ]);

        $tables = $this->abstractSourceMock->getTables(null);

        static::assertCount(1, $tables);
        static::assertInstanceOf(TableObject::class, $tables[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableThrowsExceptionForNonExistentTable(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNKNOWN_TABLE, 'non_existent'));

        $this->abstractSourceMock->getTable('non_existent', 'public');

        // Verify exception is thrown for non-existent table
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableThrowsExceptionForUnsupportedTableType(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'special_table' => ['table_type' => 'UNSUPPORTED_TYPE'],
                ],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(
            sprintf(RuntimeException::UNSUPPORTED_TABLE_TYPE, 'special_table', 'UNSUPPORTED_TYPE'),
        );

        $this->abstractSourceMock->getTable('special_table', 'public');

        // Verify exception is thrown for unsupported table type
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTableUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'table_names' => [
                'def' => [
                    'users' => ['table_type' => 'BASE TABLE'],
                ],
            ],
            'columns'     => [
                'def' => [
                    'users' => [],
                ],
            ],
            'constraints' => [
                'def' => [
                    'users' => [],
                ],
            ],
        ]);

        $table = $this->abstractSourceMock->getTable('users', null);

        static::assertInstanceOf(TableObject::class, $table);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getTrigger(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => [
                    'my_trigger' => [
                        'event_manipulation'         => 'UPDATE',
                        'event_object_catalog'       => 'main',
                        'event_object_schema'        => 'public',
                        'event_object_table'         => 'orders',
                        'action_order'               => '1',
                        'action_condition'           => 'WHEN (NEW.status != OLD.status)',
                        'action_statement'           => 'EXECUTE PROCEDURE log_change()',
                        'action_orientation'         => 'ROW',
                        'action_timing'              => 'AFTER',
                        'action_reference_old_table' => 'old_table',
                        'action_reference_new_table' => 'new_table',
                        'action_reference_old_row'   => 'OLD',
                        'action_reference_new_row'   => 'NEW',
                        'created'                    => null,
                    ],
                ],
            ],
        ]);

        $trigger = $this->abstractSourceMock->getTrigger('my_trigger', 'public');
        // Verify getTrigger returns TriggerObject with all properties

        static::assertInstanceOf(TriggerObject::class, $trigger);
        static::assertSame('my_trigger', $trigger->getName());
        static::assertSame('UPDATE', $trigger->getEventManipulation());
        static::assertSame('main', $trigger->getEventObjectCatalog());
        static::assertSame('public', $trigger->getEventObjectSchema());
        static::assertSame('orders', $trigger->getEventObjectTable());
        static::assertSame('1', $trigger->getActionOrder());
        static::assertSame('WHEN (NEW.status != OLD.status)', $trigger->getActionCondition());
        static::assertSame('EXECUTE PROCEDURE log_change()', $trigger->getActionStatement());
        static::assertSame('ROW', $trigger->getActionOrientation());
        static::assertSame('AFTER', $trigger->getActionTiming());
        static::assertSame('old_table', $trigger->getActionReferenceOldTable());
        static::assertSame('new_table', $trigger->getActionReferenceNewTable());
        static::assertSame('OLD', $trigger->getActionReferenceOldRow());
        static::assertSame('NEW', $trigger->getActionReferenceNewRow());
        static::assertNull($trigger->getCreated());
    }

    /**
     * Trigger Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggerNames(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => [
                    'audit_trigger'    => [],
                    'update_timestamp' => [],
                ],
            ],
        ]);

        $triggerNames = $this->abstractSourceMock->getTriggerNames('public');
        // Verify getTriggerNames returns array of trigger names

        static::assertSame(['audit_trigger', 'update_timestamp'], $triggerNames);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggerNamesUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'triggers' => [
                'def' => [
                    'trig1' => [],
                ],
            ],
        ]);

        $names = $this->abstractSourceMock->getTriggerNames(null);

        static::assertSame(['trig1'], $names);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggers(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => [
                    'trigger1' => [
                        'event_manipulation'         => 'INSERT',
                        'event_object_catalog'       => 'catalog',
                        'event_object_schema'        => 'public',
                        'event_object_table'         => 'users',
                        'action_order'               => '1',
                        'action_condition'           => null,
                        'action_statement'           => 'BEGIN ... END',
                        'action_orientation'         => 'ROW',
                        'action_timing'              => 'BEFORE',
                        'action_reference_old_table' => null,
                        'action_reference_new_table' => null,
                        'action_reference_old_row'   => 'OLD',
                        'action_reference_new_row'   => 'NEW',
                        'created'                    => null,
                    ],
                ],
            ],
        ]);

        $triggers = $this->abstractSourceMock->getTriggers('public');
        // Verify getTriggers returns array of TriggerObject instances

        static::assertCount(1, $triggers);
        static::assertInstanceOf(TriggerObject::class, $triggers[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggersUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'triggers' => [
                'def' => [
                    'trig1' => [
                        'event_manipulation'         => 'INSERT',
                        'event_object_catalog'       => 'cat',
                        'event_object_schema'        => 'def',
                        'event_object_table'         => 'users',
                        'action_order'               => '1',
                        'action_condition'           => null,
                        'action_statement'           => 'BEGIN END',
                        'action_orientation'         => 'ROW',
                        'action_timing'              => 'BEFORE',
                        'action_reference_old_table' => null,
                        'action_reference_new_table' => null,
                        'action_reference_old_row'   => 'OLD',
                        'action_reference_new_row'   => 'NEW',
                        'created'                    => null,
                    ],
                ],
            ],
        ]);

        $triggers = $this->abstractSourceMock->getTriggers(null);

        static::assertCount(1, $triggers);
        static::assertInstanceOf(TriggerObject::class, $triggers[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggerThrowsExceptionForNonExistent(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => [],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNKNOWN_TRIGGER, 'non_existent'));

        $this->abstractSourceMock->getTrigger('non_existent', 'public');

        // Verify exception is thrown for non-existent trigger
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getTriggerUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'triggers' => [
                'def' => [
                    'trig1' => [
                        'event_manipulation'         => 'INSERT',
                        'event_object_catalog'       => 'cat',
                        'event_object_schema'        => 'def',
                        'event_object_table'         => 'users',
                        'action_order'               => '1',
                        'action_condition'           => null,
                        'action_statement'           => 'BEGIN END',
                        'action_orientation'         => 'ROW',
                        'action_timing'              => 'BEFORE',
                        'action_reference_old_table' => null,
                        'action_reference_new_table' => null,
                        'action_reference_old_row'   => 'OLD',
                        'action_reference_new_row'   => 'NEW',
                        'created'                    => null,
                    ],
                ],
            ],
        ]);

        $trigger = $this->abstractSourceMock->getTrigger('trig1', null);

        static::assertInstanceOf(TriggerObject::class, $trigger);
        static::assertSame('trig1', $trigger->getName());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function getViewForExistingView(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'my_view' => [
                        'table_type'      => 'VIEW',
                        'view_definition' => 'SELECT * FROM users',
                        'check_option'    => 'LOCAL',
                        'is_updatable'    => true,
                    ],
                ],
            ],
            'columns'     => [
                'public' => [
                    'my_view' => [],
                ],
            ],
            'constraints' => [
                'public' => [
                    'my_view' => [],
                ],
            ],
        ]);

        $view = $this->abstractSourceMock->getView('my_view', 'public');
        // Verify getView returns ViewObject with all properties

        static::assertInstanceOf(ViewObject::class, $view);
        static::assertSame('my_view', $view->getName());
    }

    /**
     * View Methods
     *
     * @throws ReflectionException
     */
    #[Test]
    public function getViewNames(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users'         => ['table_type' => 'BASE TABLE'],
                    'user_summary'  => ['table_type' => 'VIEW'],
                    'order_summary' => ['table_type' => 'VIEW'],
                ],
            ],
        ]);

        $viewNames = $this->abstractSourceMock->getViewNames('public');

        // Verify getViewNames filters only view types
        static::assertSame(['user_summary', 'order_summary'], $viewNames);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViewNamesUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'table_names' => [
                'def' => [
                    'v1' => ['table_type' => 'VIEW'],
                ],
            ],
        ]);

        $names = $this->abstractSourceMock->getViewNames(null);

        static::assertSame(['v1'], $names);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViews(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'view1' => [
                        'table_type'      => 'VIEW',
                        'view_definition' => 'SELECT * FROM table1',
                        'check_option'    => null,
                        'is_updatable'    => true,
                    ],
                ],
            ],
            'columns'     => [
                'public' => [
                    'view1' => [],
                ],
            ],
            'constraints' => [
                'public' => [
                    'view1' => [],
                ],
            ],
        ]);

        $views = $this->abstractSourceMock->getViews('public');

        // Verify getViews returns array of ViewObject instances
        static::assertCount(1, $views);
        static::assertInstanceOf(ViewObject::class, $views[0]);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViewsUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'table_names' => [
                'def' => [
                    'v1' => [
                        'table_type'      => 'VIEW',
                        'view_definition' => 'SELECT 1',
                        'check_option'    => null,
                        'is_updatable'    => true,
                    ],
                ],
            ],
            'columns'     => ['def' => ['v1' => []]],
            'constraints' => ['def' => ['v1' => []]],
        ]);

        $views = $this->abstractSourceMock->getViews(null);

        static::assertCount(1, $views);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViewThrowsExceptionForNonExistentView(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNKNOWN_VIEW, 'non_existent_view'));

        $this->abstractSourceMock->getView('non_existent_view', 'public');

        // Verify exception is thrown for non-existent view
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViewThrowsExceptionForTable(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::UNKNOWN_VIEW, 'users'));

        $this->abstractSourceMock->getView('users', 'public');

        // Verify exception is thrown when requesting view for a table
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function getViewUsesDefaultSchemaWhenNull(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        $refProp->setValue($this->abstractSourceMock, 'def');

        $this->setMockData([
            'table_names' => [
                'def' => [
                    'v1' => [
                        'table_type'      => 'VIEW',
                        'view_definition' => 'SELECT 1',
                        'check_option'    => null,
                        'is_updatable'    => false,
                    ],
                ],
            ],
            'columns'     => ['def' => ['v1' => []]],
            'constraints' => ['def' => ['v1' => []]],
        ]);

        $view = $this->abstractSourceMock->getView('v1', null);

        static::assertInstanceOf(ViewObject::class, $view);
    }

    protected MockObject|AbstractSource $abstractSourceMock;

    protected MockObject|AdapterInterface $adapterMock;

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadColumnDataCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadColumnData');
        $method->invoke($this->abstractSourceMock, 'users', 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('columns', $data);
        static::assertArrayHasKey('test_schema', $data['columns']);
        static::assertArrayHasKey('users', $data['columns']['test_schema']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadColumnDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => ['existing_column' => []],
                ],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadColumnData');
        $method->invoke($this->abstractSourceMock, 'users', 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing_column', $data['columns']['public']['users']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintDataCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintData');
        $method->invoke($this->abstractSourceMock, 'users', 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('constraints', $data);
        static::assertArrayHasKey('test_schema', $data['constraints']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintData');
        $method->invoke($this->abstractSourceMock, 'table', 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing', $data['constraints']['public']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintDataKeysCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintDataKeys');
        $method->invoke($this->abstractSourceMock, 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('constraint_keys', $data);
        static::assertArrayHasKey('test_schema', $data['constraint_keys']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintDataKeysEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraint_keys' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintDataKeys');
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing', $data['constraint_keys']['public']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintReferencesCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintReferences');
        $method->invoke($this->abstractSourceMock, 'users', 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('constraint_references', $data);
        static::assertArrayHasKey('test_schema', $data['constraint_references']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadConstraintReferencesEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraint_references' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintReferences');
        $method->invoke($this->abstractSourceMock, 'table', 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing', $data['constraint_references']['public']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadTableNameDataCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTableNameData');
        $method->invoke($this->abstractSourceMock, 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('table_names', $data);
        static::assertArrayHasKey('test_schema', $data['table_names']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadTableNameDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTableNameData');
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing', $data['table_names']['public']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadTriggerDataCallsPrepareDataHierarchy(): void
    {
        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTriggerData');
        $method->invoke($this->abstractSourceMock, 'test_schema');

        $data = $this->getMockData();
        static::assertArrayHasKey('triggers', $data);
        static::assertArrayHasKey('test_schema', $data['triggers']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function loadTriggerDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTriggerData');
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        // Verify method returns early when data exists
        static::assertArrayHasKey('existing', $data['triggers']['public']);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function prepareDataHierarchyWithMultipleKeys(): void
    {
        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $method = new ReflectionMethod($source, 'prepareDataHierarchy');
        $method->invoke($source, 'level1', 'level2', 'level3');

        $refProp = new ReflectionProperty($source, 'data');

        $data = $refProp->getValue($source);

        // Verify nested hierarchy is created
        static::assertArrayHasKey('level1', $data);
        static::assertArrayHasKey('level2', $data['level1']);
        static::assertArrayHasKey('level3', $data['level1']['level2']);
    }

    /**
     * Helper Methods
     *
     * @throws ReflectionException
     * @throws ReflectionException
     * @throws ReflectionException
     */
    #[Test]
    public function prepareDataHierarchyWithSingleKey(): void
    {
        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $method = new ReflectionMethod($source, 'prepareDataHierarchy');
        $method->invoke($source, 'test_key');

        $refProp = new ReflectionProperty($source, 'data');

        $data = $refProp->getValue($source);

        // Verify single key hierarchy is created
        static::assertArrayHasKey('test_key', $data);
    }

    #[Override]
    protected function setUp(): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&MockObject $adapterMock */
        $adapterMock = $this->createMockForIntersectionOfInterfaces([
            AdapterInterface::class,
            SchemaAwareInterface::class,
        ]);
        $this->adapterMock = $adapterMock;

        $this->abstractSourceMock = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods([
                'loadSchemaData',
            ])
            ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    private function getMockData(): array
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'data');
        return $refProp->getValue($this->abstractSourceMock);
    }

    /**
     * @throws ReflectionException
     */
    private function setMockData(array $data): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'data');
        $refProp->setValue($this->abstractSourceMock, $data);
    }
}
