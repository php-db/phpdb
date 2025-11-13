<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Source;

use Exception;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintKeyObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\TriggerObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\Metadata\Source\AbstractSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

final class AbstractSourceTest extends TestCase
{
    protected MockObject|AbstractSource $abstractSourceMock;

    /** @var MockObject */
    protected MockObject $adapterMock;

    #[Override]
    protected function setUp(): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&MockObject $adapterMock */
        $adapterMock              = $this->createMockForIntersectionOfInterfaces([
            AdapterInterface::class,
            SchemaAwareInterface::class,
        ]);
        $this->adapterMock        = $adapterMock;
        $this->abstractSourceMock = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods([
                'loadSchemaData',
                'loadTableNameData',
                'loadColumnData',
                'loadConstraintData',
                'loadConstraintDataKeys',
                'loadConstraintReferences',
                'loadTriggerData',
            ])
            ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    private function setMockData(array $data): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'data');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);
        $refProp->setValue($this->abstractSourceMock, $data);
    }

    private function getMockData(): array
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'data');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);
        return $refProp->getValue($this->abstractSourceMock);
    }

    public function testConstructorWithSchemaFromAdapter(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([AdapterInterface::class, SchemaAwareInterface::class]);
        $adapter->method('getCurrentSchema')->willReturn('my_schema');

        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$adapter])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $refProp = new ReflectionProperty($source, 'defaultSchema');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);

        self::assertSame('my_schema', $refProp->getValue($source));
    }

    public function testConstructorWithNullSchemaUsesDefaultConstant(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([AdapterInterface::class, SchemaAwareInterface::class]);
        $adapter->method('getCurrentSchema')->willReturn(false);

        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$adapter])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $refProp = new ReflectionProperty($source, 'defaultSchema');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);

        self::assertSame(AbstractSource::DEFAULT_SCHEMA, $refProp->getValue($source));
    }

    /**
     * Schema Methods
     */
    public function testGetSchemasCallsLoadSchemaData(): void
    {
        $this->abstractSourceMock->expects($this->once())
            ->method('loadSchemaData');

        $this->setMockData(['schemas' => ['schema1', 'schema2']]);

        $schemas = $this->abstractSourceMock->getSchemas();

        self::assertSame(['schema1', 'schema2'], $schemas);
    }

    /**
     * Table Name Methods
     */
    public function testGetTableNamesWithNullSchemaUsesDefault(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'defaultSchema');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);
        $refProp->setValue($this->abstractSourceMock, 'default_schema');

        $this->setMockData([
            'table_names' => [
                'default_schema' => [
                    'users'  => ['table_type' => 'BASE TABLE'],
                    'orders' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        $tableNames = $this->abstractSourceMock->getTableNames();

        self::assertSame(['users', 'orders'], $tableNames);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetTableNamesWithSpecificSchema(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'products' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        $tableNames = $this->abstractSourceMock->getTableNames('public');

        self::assertSame(['products'], $tableNames);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetTableNamesExcludesViewsByDefault(): void
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

        $tableNames = $this->abstractSourceMock->getTableNames('public');

        self::assertSame(['users', 'orders'], $tableNames);
    }

    public function testGetTableNamesIncludesViewsWhenRequested(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users'        => ['table_type' => 'BASE TABLE'],
                    'user_summary' => ['table_type' => 'VIEW'],
                ],
            ],
        ]);

        $tableNames = $this->abstractSourceMock->getTableNames('public', true);

        self::assertSame(['users', 'user_summary'], $tableNames);
    }

    /**
     * Table Object Methods
     */
    public function testGetTables(): void
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

        $tables = $this->abstractSourceMock->getTables('public');

        self::assertCount(2, $tables);
        self::assertInstanceOf(TableObject::class, $tables[0]);
        self::assertInstanceOf(TableObject::class, $tables[1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetTableForBaseTable(): void
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

        $table = $this->abstractSourceMock->getTable('users', 'public');

        self::assertInstanceOf(TableObject::class, $table);
        self::assertSame('users', $table->getName());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetTableForView(): void
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

        self::assertInstanceOf(ViewObject::class, $view);
        self::assertSame('user_summary', $view->getName());
        self::assertSame('SELECT id, name FROM users', $view->getViewDefinition());
        self::assertSame('CASCADED', $view->getCheckOption());
        self::assertFalse($view->getIsUpdatable());
    }

    public function testGetTableThrowsExceptionForNonExistentTable(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Table "non_existent" does not exist');

        $this->abstractSourceMock->getTable('non_existent', 'public');
    }

    public function testGetTableThrowsExceptionForUnsupportedTableType(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'special_table' => ['table_type' => 'UNSUPPORTED_TYPE'],
                ],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Table "special_table" is of an unsupported type "UNSUPPORTED_TYPE"');

        $this->abstractSourceMock->getTable('special_table', 'public');
    }

    /**
     * View Methods
     */
    public function testGetViewNames(): void
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

        self::assertSame(['user_summary', 'order_summary'], $viewNames);
    }

    public function testGetViews(): void
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

        self::assertCount(1, $views);
        self::assertInstanceOf(ViewObject::class, $views[0]);
    }

    public function testGetViewForExistingView(): void
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

        self::assertInstanceOf(ViewObject::class, $view);
        self::assertSame('my_view', $view->getName());
    }

    public function testGetViewThrowsExceptionForNonExistentView(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('View "non_existent_view" does not exist');

        $this->abstractSourceMock->getView('non_existent_view', 'public');
    }

    public function testGetViewThrowsExceptionForTable(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => [
                    'users' => ['table_type' => 'BASE TABLE'],
                ],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('View "users" does not exist');

        $this->abstractSourceMock->getView('users', 'public');
    }

    /**
     * Column Methods
     */
    public function testGetColumnNames(): void
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

        self::assertSame(['id', 'username', 'email'], $columnNames);
    }

    public function testGetColumnNamesThrowsExceptionForNonExistentTable(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"non_existent" does not exist');

        $this->abstractSourceMock->getColumnNames('non_existent', 'public');
    }

    public function testGetColumns(): void
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

        self::assertCount(1, $columns);
        self::assertInstanceOf(ColumnObject::class, $columns[0]);
        self::assertSame('id', $columns[0]->getName());
    }

    public function testGetColumn(): void
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

        self::assertInstanceOf(ColumnObject::class, $column);
        self::assertSame('username', $column->getName());
        self::assertSame('users', $column->getTableName());
        self::assertSame('public', $column->getSchemaName());
        self::assertSame(2, $column->getOrdinalPosition());
        self::assertSame('', $column->getColumnDefault());
        self::assertFalse($column->getIsNullable());
        self::assertSame('VARCHAR', $column->getDataType());
        self::assertSame(255, $column->getCharacterMaximumLength());
        self::assertSame(1024, $column->getCharacterOctetLength());
        self::assertNull($column->getNumericPrecision());
        self::assertNull($column->getNumericScale());
        self::assertNull($column->getNumericUnsigned());
        self::assertSame('utf8_general_ci', $column->getErrata('collation'));
    }

    public function testGetColumnThrowsExceptionForNonExistentColumn(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => [],
                ],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A column by that name was not found.');

        $this->abstractSourceMock->getColumn('non_existent', 'users', 'public');
    }

    /**
     * Constraint Methods
     */
    public function testGetConstraints(): void
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

        self::assertCount(2, $constraints);
        self::assertInstanceOf(ConstraintObject::class, $constraints[0]);
        self::assertInstanceOf(ConstraintObject::class, $constraints[1]);
    }

    public function testGetConstraint(): void
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

        self::assertInstanceOf(ConstraintObject::class, $constraint);
        self::assertSame('fk_orders_user', $constraint->getName());
        self::assertSame('orders', $constraint->getTableName());
        self::assertSame('public', $constraint->getSchemaName());
        self::assertSame('FOREIGN KEY', $constraint->getType());
        self::assertSame(['user_id'], $constraint->getColumns());
        self::assertSame('public', $constraint->getReferencedTableSchema());
        self::assertSame('users', $constraint->getReferencedTableName());
        self::assertSame(['id'], $constraint->getReferencedColumns());
        self::assertSame('SIMPLE', $constraint->getMatchOption());
        self::assertSame('CASCADE', $constraint->getUpdateRule());
        self::assertSame('RESTRICT', $constraint->getDeleteRule());
    }

    public function testGetConstraintWithCheckClause(): void
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

        self::assertSame('CHECK', $constraint->getType());
        self::assertSame('age >= 18', $constraint->getCheckClause());
    }

    public function testGetConstraintThrowsExceptionForNonExistent(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => [
                    'users' => [],
                ],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot find a constraint by that name in this table');

        $this->abstractSourceMock->getConstraint('non_existent', 'users', 'public');
    }

    /**
     * Constraint Key Methods
     *
     * @throws ReflectionException
     */
    public function testGetConstraintKeys(): void
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
        $constraints = $this->abstractSourceMock->getConstraintKeys('bam_constraint', 'bar_table', 'foo_schema');
        self::assertCount(1, $constraints);

        $constraintKeyObj = $constraints[0];
        self::assertInstanceOf(ConstraintKeyObject::class, $constraintKeyObj);

        // check value object is mapped correctly
        self::assertEquals('a', $constraintKeyObj->getColumnName());
        self::assertEquals(1, $constraintKeyObj->getOrdinalPosition());
        self::assertEquals('another_table', $constraintKeyObj->getReferencedTableName());
        self::assertEquals('another_column', $constraintKeyObj->getReferencedColumnName());
        self::assertEquals('UP', $constraintKeyObj->getForeignKeyUpdateRule());
        self::assertEquals('DOWN', $constraintKeyObj->getForeignKeyDeleteRule());
    }

    public function testGetConstraintKeysWithMultipleKeys(): void
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
        $keys = $this->abstractSourceMock->getConstraintKeys('fk_composite', 'my_table', 'public');

        self::assertCount(2, $keys);
        self::assertSame('col1', $keys[0]->getColumnName());
        self::assertSame('col2', $keys[1]->getColumnName());
    }

    public function testGetConstraintKeysWithoutReferences(): void
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
        $keys = $this->abstractSourceMock->getConstraintKeys('pk_users', 'users', 'public');

        self::assertCount(1, $keys);
        self::assertSame('id', $keys[0]->getColumnName());
        self::assertNull($keys[0]->getReferencedTableName());
    }

    /**
     * Trigger Methods
     */
    public function testGetTriggerNames(): void
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

        self::assertSame(['audit_trigger', 'update_timestamp'], $triggerNames);
    }

    public function testGetTriggers(): void
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

        self::assertCount(1, $triggers);
        self::assertInstanceOf(TriggerObject::class, $triggers[0]);
    }

    public function testGetTrigger(): void
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

        self::assertInstanceOf(TriggerObject::class, $trigger);
        self::assertSame('my_trigger', $trigger->getName());
        self::assertSame('UPDATE', $trigger->getEventManipulation());
        self::assertSame('main', $trigger->getEventObjectCatalog());
        self::assertSame('public', $trigger->getEventObjectSchema());
        self::assertSame('orders', $trigger->getEventObjectTable());
        self::assertSame('1', $trigger->getActionOrder());
        self::assertSame('WHEN (NEW.status != OLD.status)', $trigger->getActionCondition());
        self::assertSame('EXECUTE PROCEDURE log_change()', $trigger->getActionStatement());
        self::assertSame('ROW', $trigger->getActionOrientation());
        self::assertSame('AFTER', $trigger->getActionTiming());
        self::assertSame('old_table', $trigger->getActionReferenceOldTable());
        self::assertSame('new_table', $trigger->getActionReferenceNewTable());
        self::assertSame('OLD', $trigger->getActionReferenceOldRow());
        self::assertSame('NEW', $trigger->getActionReferenceNewRow());
        self::assertNull($trigger->getCreated());
    }

    public function testGetTriggerThrowsExceptionForNonExistent(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => [],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Trigger "non_existent" does not exist');

        $this->abstractSourceMock->getTrigger('non_existent', 'public');
    }

    /**
     * Helper Methods
     */
    public function testPrepareDataHierarchyWithSingleKey(): void
    {
        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $method = new ReflectionMethod($source, 'prepareDataHierarchy');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($source, 'test_key');

        $refProp = new ReflectionProperty($source, 'data');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);
        $data = $refProp->getValue($source);

        self::assertArrayHasKey('test_key', $data);
    }

    public function testPrepareDataHierarchyWithMultipleKeys(): void
    {
        $source = $this->getMockBuilder(AbstractSource::class)
            ->setConstructorArgs([$this->adapterMock])
            ->onlyMethods(['loadSchemaData'])
            ->getMock();

        $method = new ReflectionMethod($source, 'prepareDataHierarchy');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($source, 'level1', 'level2', 'level3');

        $refProp = new ReflectionProperty($source, 'data');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $refProp->setAccessible(true);
        $data = $refProp->getValue($source);

        self::assertArrayHasKey('level1', $data);
        self::assertArrayHasKey('level2', $data['level1']);
        self::assertArrayHasKey('level3', $data['level1']['level2']);
    }

    public function testLoadTableNameDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'table_names' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTableNameData');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing', $data['table_names']['public']);
    }

    public function testLoadColumnDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'columns' => [
                'public' => [
                    'users' => ['existing_column' => []],
                ],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadColumnData');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'users', 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing_column', $data['columns']['public']['users']);
    }

    public function testLoadConstraintDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraints' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintData');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'table', 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing', $data['constraints']['public']);
    }

    public function testLoadConstraintDataKeysEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraint_keys' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintDataKeys');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing', $data['constraint_keys']['public']);
    }

    public function testLoadConstraintReferencesEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'constraint_references' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadConstraintReferences');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'table', 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing', $data['constraint_references']['public']);
    }

    public function testLoadTriggerDataEarlyReturnWhenDataExists(): void
    {
        $this->setMockData([
            'triggers' => [
                'public' => ['existing' => []],
            ],
        ]);

        $method = new ReflectionMethod($this->abstractSourceMock, 'loadTriggerData');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);
        $method->invoke($this->abstractSourceMock, 'public');

        $data = $this->getMockData();
        self::assertArrayHasKey('existing', $data['triggers']['public']);
    }
}
