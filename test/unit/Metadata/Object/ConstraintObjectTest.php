<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\ConstraintObject;
use PHPUnit\Framework\TestCase;

final class ConstraintObjectTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $constraint = new ConstraintObject('constraint_name', 'table_name', 'schema_name');

        // Verify all constructor parameters are set
        self::assertSame('constraint_name', $constraint->getName());
        self::assertSame('table_name', $constraint->getTableName());
        self::assertSame('schema_name', $constraint->getSchemaName());
    }

    public function testConstructorWithNullSchema(): void
    {
        $constraint = new ConstraintObject('constraint_name', 'table_name');

        // Verify schema defaults to null
        self::assertSame('constraint_name', $constraint->getName());
        self::assertSame('table_name', $constraint->getTableName());
        self::assertNull($constraint->getSchemaName());
    }

    public function testSetNameAndGetName(): void
    {
        $constraint = new ConstraintObject('initial', 'table', 'schema');

        // Update name and verify change
        $constraint->setName('new_name');
        self::assertSame('new_name', $constraint->getName());
    }

    public function testSetSchemaNameAndGetSchemaName(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'initial_schema');

        // Update schema and verify change
        $constraint->setSchemaName('new_schema');
        self::assertSame('new_schema', $constraint->getSchemaName());
    }

    public function testSetTableNameAndGetTableNameWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'initial_table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setTableName('new_table');
        self::assertSame($constraint, $result);
        self::assertSame('new_table', $constraint->getTableName());
    }

    public function testSetTypeAndGetType(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Set type and verify retrieval
        $constraint->setType('PRIMARY KEY');
        self::assertSame('PRIMARY KEY', $constraint->getType());

        // Verify mutation to different type
        $constraint->setType('UNIQUE');
        self::assertSame('UNIQUE', $constraint->getType());
    }

    public function testHasColumnsReturnsFalseWhenEmpty(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify hasColumns returns false when not set
        self::assertFalse($constraint->hasColumns());
    }

    public function testHasColumnsReturnsTrueWhenPopulated(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify hasColumns returns true after setting columns
        $constraint->setColumns(['col1', 'col2']);
        self::assertTrue($constraint->hasColumns());
    }

    public function testSetColumnsAndGetColumnsWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');
        $columns    = ['column1', 'column2', 'column3'];

        // Verify fluent interface and value update
        $result = $constraint->setColumns($columns);
        self::assertSame($constraint, $result);
        self::assertSame($columns, $constraint->getColumns());
    }

    public function testSetColumnsWithEmptyArray(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');
        $constraint->setColumns(['col1']);

        // Set empty array and verify hasColumns reflects change
        $constraint->setColumns([]);
        self::assertSame([], $constraint->getColumns());
        self::assertFalse($constraint->hasColumns());
    }

    public function testSetReferencedTableSchemaAndGetReferencedTableSchemaWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setReferencedTableSchema('ref_schema');
        self::assertSame($constraint, $result);
        self::assertSame('ref_schema', $constraint->getReferencedTableSchema());
    }

    public function testSetReferencedTableNameAndGetReferencedTableNameWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setReferencedTableName('ref_table');
        self::assertSame($constraint, $result);
        self::assertSame('ref_table', $constraint->getReferencedTableName());
    }

    public function testSetReferencedColumnsAndGetReferencedColumnsWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');
        $columns    = ['ref_col1', 'ref_col2'];

        // Verify fluent interface and value update
        $result = $constraint->setReferencedColumns($columns);
        self::assertSame($constraint, $result);
        self::assertSame($columns, $constraint->getReferencedColumns());
    }

    public function testSetMatchOptionAndGetMatchOptionWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setMatchOption('FULL');
        self::assertSame($constraint, $result);
        self::assertSame('FULL', $constraint->getMatchOption());
    }

    public function testSetUpdateRuleAndGetUpdateRuleWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setUpdateRule('CASCADE');
        self::assertSame($constraint, $result);
        self::assertSame('CASCADE', $constraint->getUpdateRule());
    }

    public function testSetDeleteRuleAndGetDeleteRuleWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setDeleteRule('RESTRICT');
        self::assertSame($constraint, $result);
        self::assertSame('RESTRICT', $constraint->getDeleteRule());
    }

    public function testSetCheckClauseAndGetCheckClauseWithFluentInterface(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $constraint->setCheckClause('age >= 18');
        self::assertSame($constraint, $result);
        self::assertSame('age >= 18', $constraint->getCheckClause());
    }

    public function testIsPrimaryKeyReturnsTrueForPrimaryKey(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isPrimaryKey returns true for PRIMARY KEY type
        $constraint->setType('PRIMARY KEY');
        self::assertTrue($constraint->isPrimaryKey());
    }

    public function testIsPrimaryKeyReturnsFalseForOtherTypes(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isPrimaryKey returns false for UNIQUE
        $constraint->setType('UNIQUE');
        self::assertFalse($constraint->isPrimaryKey());

        // Verify isPrimaryKey returns false for FOREIGN KEY
        $constraint->setType('FOREIGN KEY');
        self::assertFalse($constraint->isPrimaryKey());

        // Verify isPrimaryKey returns false for CHECK
        $constraint->setType('CHECK');
        self::assertFalse($constraint->isPrimaryKey());
    }

    public function testIsUniqueReturnsTrueForUnique(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isUnique returns true for UNIQUE type
        $constraint->setType('UNIQUE');
        self::assertTrue($constraint->isUnique());
    }

    public function testIsUniqueReturnsFalseForOtherTypes(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isUnique returns false for PRIMARY KEY
        $constraint->setType('PRIMARY KEY');
        self::assertFalse($constraint->isUnique());

        // Verify isUnique returns false for FOREIGN KEY
        $constraint->setType('FOREIGN KEY');
        self::assertFalse($constraint->isUnique());

        // Verify isUnique returns false for CHECK
        $constraint->setType('CHECK');
        self::assertFalse($constraint->isUnique());
    }

    public function testIsForeignKeyReturnsTrueForForeignKey(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isForeignKey returns true for FOREIGN KEY type
        $constraint->setType('FOREIGN KEY');
        self::assertTrue($constraint->isForeignKey());
    }

    public function testIsForeignKeyReturnsFalseForOtherTypes(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isForeignKey returns false for PRIMARY KEY
        $constraint->setType('PRIMARY KEY');
        self::assertFalse($constraint->isForeignKey());

        // Verify isForeignKey returns false for UNIQUE
        $constraint->setType('UNIQUE');
        self::assertFalse($constraint->isForeignKey());

        // Verify isForeignKey returns false for CHECK
        $constraint->setType('CHECK');
        self::assertFalse($constraint->isForeignKey());
    }

    public function testIsCheckReturnsTrueForCheck(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isCheck returns true for CHECK type
        $constraint->setType('CHECK');
        self::assertTrue($constraint->isCheck());
    }

    public function testIsCheckReturnsFalseForOtherTypes(): void
    {
        $constraint = new ConstraintObject('name', 'table', 'schema');

        // Verify isCheck returns false for PRIMARY KEY
        $constraint->setType('PRIMARY KEY');
        self::assertFalse($constraint->isCheck());

        // Verify isCheck returns false for UNIQUE
        $constraint->setType('UNIQUE');
        self::assertFalse($constraint->isCheck());

        // Verify isCheck returns false for FOREIGN KEY
        $constraint->setType('FOREIGN KEY');
        self::assertFalse($constraint->isCheck());
    }

    public function testCompletePrimaryKeyConstraint(): void
    {
        $constraint = new ConstraintObject('pk_users', 'users', 'public');
        $constraint->setType('PRIMARY KEY');
        $constraint->setColumns(['id']);

        // Verify primary key constraint is configured correctly
        self::assertSame('pk_users', $constraint->getName());
        self::assertSame('users', $constraint->getTableName());
        self::assertSame('public', $constraint->getSchemaName());
        self::assertTrue($constraint->isPrimaryKey());
        self::assertFalse($constraint->isUnique());
        self::assertFalse($constraint->isForeignKey());
        self::assertFalse($constraint->isCheck());
        self::assertSame(['id'], $constraint->getColumns());
        self::assertTrue($constraint->hasColumns());
    }

    public function testCompleteForeignKeyConstraint(): void
    {
        $constraint = new ConstraintObject('fk_orders_user', 'orders', 'public');
        $constraint->setType('FOREIGN KEY');
        $constraint->setColumns(['user_id'])
            ->setReferencedTableSchema('public')
            ->setReferencedTableName('users')
            ->setReferencedColumns(['id'])
            ->setMatchOption('SIMPLE')
            ->setUpdateRule('CASCADE')
            ->setDeleteRule('RESTRICT');

        // Verify foreign key constraint is configured correctly
        self::assertSame('fk_orders_user', $constraint->getName());
        self::assertTrue($constraint->isForeignKey());
        self::assertFalse($constraint->isPrimaryKey());
        self::assertFalse($constraint->isUnique());
        self::assertFalse($constraint->isCheck());
        self::assertSame(['user_id'], $constraint->getColumns());
        self::assertSame('public', $constraint->getReferencedTableSchema());
        self::assertSame('users', $constraint->getReferencedTableName());
        self::assertSame(['id'], $constraint->getReferencedColumns());
        self::assertSame('SIMPLE', $constraint->getMatchOption());
        self::assertSame('CASCADE', $constraint->getUpdateRule());
        self::assertSame('RESTRICT', $constraint->getDeleteRule());
    }

    public function testCompleteUniqueConstraint(): void
    {
        $constraint = new ConstraintObject('uq_users_email', 'users', 'public');
        $constraint->setType('UNIQUE');
        $constraint->setColumns(['email']);

        // Verify unique constraint is configured correctly
        self::assertTrue($constraint->isUnique());
        self::assertFalse($constraint->isPrimaryKey());
        self::assertFalse($constraint->isForeignKey());
        self::assertFalse($constraint->isCheck());
        self::assertSame(['email'], $constraint->getColumns());
    }

    public function testCompleteCheckConstraint(): void
    {
        $constraint = new ConstraintObject('chk_users_age', 'users', 'public');
        $constraint->setType('CHECK');
        $constraint->setCheckClause('age >= 18 AND age <= 120');

        // Verify check constraint is configured correctly
        self::assertTrue($constraint->isCheck());
        self::assertFalse($constraint->isPrimaryKey());
        self::assertFalse($constraint->isUnique());
        self::assertFalse($constraint->isForeignKey());
        self::assertSame('age >= 18 AND age <= 120', $constraint->getCheckClause());
    }
}
