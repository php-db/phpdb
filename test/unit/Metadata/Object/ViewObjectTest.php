<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\AbstractTableObject;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\ViewObject;
use PHPUnit\Framework\TestCase;

final class ViewObjectTest extends TestCase
{
    public function testExtendsAbstractTableObject(): void
    {
        $view = new ViewObject('view_name');

        // Verify view extends AbstractTableObject
        self::assertInstanceOf(AbstractTableObject::class, $view);
    }

    public function testConstructorWithName(): void
    {
        $view = new ViewObject('user_summary');

        // Verify name is set by constructor
        self::assertSame('user_summary', $view->getName());
    }

    public function testConstructorWithNullName(): void
    {
        $view = new ViewObject();

        // Verify name defaults to null when not provided
        self::assertNull($view->getName());
    }

    public function testSetViewDefinitionAndGetViewDefinitionWithFluentInterface(): void
    {
        $view       = new ViewObject('view');
        $definition = 'SELECT id, name FROM users WHERE active = 1';

        // Verify fluent interface and value update
        $result = $view->setViewDefinition($definition);
        self::assertSame($view, $result);
        self::assertSame($definition, $view->getViewDefinition());
    }

    public function testSetViewDefinitionWithNull(): void
    {
        $view = new ViewObject('view');
        $view->setViewDefinition('SELECT * FROM table');

        // Set definition to null and verify
        $view->setViewDefinition(null);
        self::assertNull($view->getViewDefinition());
    }

    public function testSetCheckOptionAndGetCheckOptionWithFluentInterface(): void
    {
        $view = new ViewObject('view');

        // Verify fluent interface and value update
        $result = $view->setCheckOption('CASCADED');
        self::assertSame($view, $result);
        self::assertSame('CASCADED', $view->getCheckOption());
    }

    public function testSetCheckOptionWithNull(): void
    {
        $view = new ViewObject('view');
        $view->setCheckOption('LOCAL');

        // Set check option to null and verify
        $view->setCheckOption(null);
        self::assertNull($view->getCheckOption());
    }

    public function testSetIsUpdatableAndGetIsUpdatableWithFluentInterface(): void
    {
        $view = new ViewObject('view');

        // Verify fluent interface and value update
        $result = $view->setIsUpdatable(true);
        self::assertSame($view, $result);
        self::assertTrue($view->getIsUpdatable());
    }

    public function testSetIsUpdatableWithFalse(): void
    {
        $view = new ViewObject('view');

        // Set updatable to false and verify
        $view->setIsUpdatable(false);
        self::assertFalse($view->getIsUpdatable());
    }

    public function testSetIsUpdatableWithNull(): void
    {
        $view = new ViewObject('view');
        $view->setIsUpdatable(true);

        // Set updatable to null and verify
        $view->setIsUpdatable(null);
        self::assertNull($view->getIsUpdatable());
    }

    public function testIsUpdatableAlias(): void
    {
        $view = new ViewObject('view');

        // Verify alias returns same value when true
        $view->setIsUpdatable(true);
        self::assertTrue($view->isUpdatable());
        self::assertSame($view->getIsUpdatable(), $view->isUpdatable());

        // Verify alias returns same value when false
        $view->setIsUpdatable(false);
        self::assertFalse($view->isUpdatable());
        self::assertSame($view->getIsUpdatable(), $view->isUpdatable());
    }

    public function testIsUpdatableAliasWithNull(): void
    {
        $view = new ViewObject('view');

        // Verify alias returns same value when null
        $view->setIsUpdatable(null);
        self::assertNull($view->isUpdatable());
        self::assertSame($view->getIsUpdatable(), $view->isUpdatable());
    }

    public function testInheritedColumnsWork(): void
    {
        $view    = new ViewObject('user_summary');
        $columns = [
            new ColumnObject('id', 'user_summary', 'public'),
            new ColumnObject('username', 'user_summary', 'public'),
        ];

        // Verify inherited setColumns method stores columns
        $view->setColumns($columns);
        self::assertSame($columns, $view->getColumns());
        self::assertCount(2, $view->getColumns());
    }

    public function testInheritedConstraintsWork(): void
    {
        $view        = new ViewObject('user_summary');
        $constraints = [
            new ConstraintObject('uq_summary', 'user_summary', 'public'),
        ];

        // Verify inherited setConstraints method stores constraints
        $view->setConstraints($constraints);
        self::assertSame($constraints, $view->getConstraints());
        self::assertCount(1, $view->getConstraints());
    }

    public function testCompleteViewObjectWithAllProperties(): void
    {
        $view = new ViewObject('active_users');

        $definition = "SELECT id, username, email FROM users WHERE status = 'active'";
        $view->setViewDefinition($definition)
            ->setCheckOption('CASCADED')
            ->setIsUpdatable(false);

        $columns = [
            new ColumnObject('id', 'active_users', 'public'),
            new ColumnObject('username', 'active_users', 'public'),
            new ColumnObject('email', 'active_users', 'public'),
        ];
        $view->setColumns($columns);

        // Verify all properties are set correctly
        self::assertSame('active_users', $view->getName());
        self::assertSame($definition, $view->getViewDefinition());
        self::assertSame('CASCADED', $view->getCheckOption());
        self::assertFalse($view->isUpdatable());
        self::assertFalse($view->getIsUpdatable());
        self::assertCount(3, $view->getColumns());
    }

    public function testViewObjectWithNullProperties(): void
    {
        $view = new ViewObject('simple_view');

        // Verify all properties default to null
        self::assertNull($view->getViewDefinition());
        self::assertNull($view->getCheckOption());
        self::assertNull($view->getIsUpdatable());
        self::assertNull($view->isUpdatable());
    }

    public function testViewObjectWithInheritedSetName(): void
    {
        $view = new ViewObject('initial_view');

        // Verify inherited setName method updates the name
        $view->setName('renamed_view');
        self::assertSame('renamed_view', $view->getName());
    }
}
