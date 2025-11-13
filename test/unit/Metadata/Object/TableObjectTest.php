<?php

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\AbstractTableObject;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PHPUnit\Framework\TestCase;

final class TableObjectTest extends TestCase
{
    public function testExtendsAbstractTableObject(): void
    {
        $table = new TableObject('table_name');

        self::assertInstanceOf(AbstractTableObject::class, $table);
    }

    public function testConstructorWithName(): void
    {
        $table = new TableObject('users');

        self::assertSame('users', $table->getName());
    }

    public function testConstructorWithNullName(): void
    {
        $table = new TableObject(null);

        self::assertNull($table->getName());
    }

    public function testInheritedSetNameWorks(): void
    {
        $table = new TableObject('initial');
        $table->setName('updated');

        self::assertSame('updated', $table->getName());
    }

    public function testInheritedSetColumnsWorks(): void
    {
        $table = new TableObject('users');
        $columns = [
            new ColumnObject('id', 'users', 'public'),
            new ColumnObject('name', 'users', 'public'),
        ];
        $table->setColumns($columns);

        self::assertSame($columns, $table->getColumns());
        self::assertCount(2, $table->getColumns());
    }

    public function testInheritedSetConstraintsWorks(): void
    {
        $table = new TableObject('users');
        $constraints = [
            new ConstraintObject('pk_users', 'users', 'public'),
        ];
        $table->setConstraints($constraints);

        self::assertSame($constraints, $table->getConstraints());
        self::assertCount(1, $table->getConstraints());
    }

    public function testCompleteTableObjectWithAllInheritedFunctionality(): void
    {
        $table = new TableObject('orders');

        $columns = [
            new ColumnObject('id', 'orders', 'public'),
            new ColumnObject('user_id', 'orders', 'public'),
            new ColumnObject('total', 'orders', 'public'),
            new ColumnObject('created_at', 'orders', 'public'),
        ];

        $constraints = [
            new ConstraintObject('pk_orders', 'orders', 'public'),
            new ConstraintObject('fk_orders_user', 'orders', 'public'),
        ];

        $table->setColumns($columns);
        $table->setConstraints($constraints);

        self::assertSame('orders', $table->getName());
        self::assertCount(4, $table->getColumns());
        self::assertCount(2, $table->getConstraints());
        self::assertInstanceOf(AbstractTableObject::class, $table);
    }

    public function testCanBeInstantiated(): void
    {
        $table = new TableObject('test_table');

        self::assertInstanceOf(TableObject::class, $table);
        self::assertSame('test_table', $table->getName());
    }
}