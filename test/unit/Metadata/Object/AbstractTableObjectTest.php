<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\AbstractTableObject;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PHPUnit\Framework\TestCase;

final class AbstractTableObjectTest extends TestCase
{
    private function createConcreteTableObject(?string $name): AbstractTableObject
    {
        return new class ($name) extends AbstractTableObject {
        };
    }

    public function testConstructorWithName(): void
    {
        $table = $this->createConcreteTableObject('table_name');

        self::assertSame('table_name', $table->getName());
    }

    public function testConstructorWithNullName(): void
    {
        $table = $this->createConcreteTableObject(null);

        self::assertNull($table->getName());
    }

    public function testConstructorWithEmptyString(): void
    {
        $table = $this->createConcreteTableObject('');

        self::assertNull($table->getName());
    }

    public function testSetNameAndGetName(): void
    {
        $table = $this->createConcreteTableObject('initial_name');
        $table->setName('new_name');

        self::assertSame('new_name', $table->getName());
    }

    public function testSetColumnsAndGetColumns(): void
    {
        $table   = $this->createConcreteTableObject('table');
        $columns = [
            new ColumnObject('id', 'table', 'schema'),
            new ColumnObject('name', 'table', 'schema'),
        ];
        $table->setColumns($columns);

        self::assertSame($columns, $table->getColumns());
    }

    public function testSetColumnsWithEmptyArray(): void
    {
        $table = $this->createConcreteTableObject('table');
        $table->setColumns([]);

        self::assertSame([], $table->getColumns());
    }

    public function testSetConstraintsAndGetConstraints(): void
    {
        $table       = $this->createConcreteTableObject('table');
        $constraints = [
            new ConstraintObject('pk_table', 'table', 'schema'),
            new ConstraintObject('fk_table', 'table', 'schema'),
        ];
        $table->setConstraints($constraints);

        self::assertSame($constraints, $table->getConstraints());
    }

    public function testSetConstraintsWithEmptyArray(): void
    {
        $table = $this->createConcreteTableObject('table');
        $table->setConstraints([]);

        self::assertSame([], $table->getConstraints());
    }

    public function testCompleteTableObjectWithAllProperties(): void
    {
        $table = $this->createConcreteTableObject('users');

        $columns = [
            new ColumnObject('id', 'users', 'public'),
            new ColumnObject('username', 'users', 'public'),
            new ColumnObject('email', 'users', 'public'),
        ];

        $constraints = [
            new ConstraintObject('pk_users', 'users', 'public'),
            new ConstraintObject('uq_users_email', 'users', 'public'),
        ];

        $table->setColumns($columns);
        $table->setConstraints($constraints);

        self::assertSame('users', $table->getName());
        self::assertSame($columns, $table->getColumns());
        self::assertCount(3, $table->getColumns());
        self::assertSame($constraints, $table->getConstraints());
        self::assertCount(2, $table->getConstraints());
    }

    public function testGetColumnsReturnsNullWhenNotSet(): void
    {
        $table = $this->createConcreteTableObject('table');

        self::assertNull($table->getColumns());
    }

    public function testGetConstraintsReturnsNullWhenNotSet(): void
    {
        $table = $this->createConcreteTableObject('table');

        self::assertNull($table->getConstraints());
    }
}
