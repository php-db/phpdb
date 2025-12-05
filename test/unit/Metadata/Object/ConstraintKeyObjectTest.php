<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\ConstraintKeyObject;
use PHPUnit\Framework\TestCase;

final class ConstraintKeyObjectTest extends TestCase
{
    public function testConstructorSetsColumnName(): void
    {
        $constraintKey = new ConstraintKeyObject('column_name');

        // Verify column name is set by constructor
        self::assertSame('column_name', $constraintKey->getColumnName());
    }

    public function testForeignKeyConstants(): void
    {
        // Verify all foreign key constants are defined correctly
        self::assertSame('CASCADE', ConstraintKeyObject::FK_CASCADE);
        self::assertSame('SET NULL', ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('NO ACTION', ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('RESTRICT', ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('SET DEFAULT', ConstraintKeyObject::FK_SET_DEFAULT);
    }

    public function testSetColumnNameAndGetColumnNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('initial');

        // Verify fluent interface and value update
        $result = $constraintKey->setColumnName('new_column');
        self::assertSame($constraintKey, $result);
        self::assertSame('new_column', $constraintKey->getColumnName());
    }

    public function testSetOrdinalPositionAndGetOrdinalPositionWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify fluent interface and value update
        $result = $constraintKey->setOrdinalPosition(3);
        self::assertSame($constraintKey, $result);
        self::assertSame(3, $constraintKey->getOrdinalPosition());
    }

    public function testSetPositionInUniqueConstraintAndGetPositionInUniqueConstraintWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify fluent interface and value update
        $result = $constraintKey->setPositionInUniqueConstraint(true);
        self::assertSame($constraintKey, $result);
        self::assertTrue($constraintKey->getPositionInUniqueConstraint());
    }

    public function testSetReferencedTableSchemaAndGetReferencedTableSchemaWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify fluent interface and value update
        $result = $constraintKey->setReferencedTableSchema('ref_schema');
        self::assertSame($constraintKey, $result);
        self::assertSame('ref_schema', $constraintKey->getReferencedTableSchema());
    }

    public function testSetReferencedTableNameAndGetReferencedTableNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify fluent interface and value update
        $result = $constraintKey->setReferencedTableName('ref_table');
        self::assertSame($constraintKey, $result);
        self::assertSame('ref_table', $constraintKey->getReferencedTableName());
    }

    public function testSetReferencedColumnNameAndGetReferencedColumnNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify fluent interface and value update
        $result = $constraintKey->setReferencedColumnName('ref_column');
        self::assertSame($constraintKey, $result);
        self::assertSame('ref_column', $constraintKey->getReferencedColumnName());
    }

    public function testSetForeignKeyUpdateRuleAndGetForeignKeyUpdateRule(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Set update rule and verify retrieval
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyUpdateRule());

        // Verify mutation by changing to different value
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyUpdateRule());
    }

    public function testSetForeignKeyUpdateRuleWithAllConstants(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify CASCADE constant
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyUpdateRule());

        // Verify SET NULL constant
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('SET NULL', $constraintKey->getForeignKeyUpdateRule());

        // Verify NO ACTION constant
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('NO ACTION', $constraintKey->getForeignKeyUpdateRule());

        // Verify RESTRICT constant
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyUpdateRule());

        // Verify SET DEFAULT constant
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_SET_DEFAULT);
        self::assertSame('SET DEFAULT', $constraintKey->getForeignKeyUpdateRule());
    }

    public function testSetForeignKeyDeleteRuleAndGetForeignKeyDeleteRule(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Set delete rule and verify retrieval
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyDeleteRule());

        // Verify mutation by changing to different value
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyDeleteRule());
    }

    public function testSetForeignKeyDeleteRuleWithAllConstants(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        // Verify CASCADE constant
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyDeleteRule());

        // Verify SET NULL constant
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('SET NULL', $constraintKey->getForeignKeyDeleteRule());

        // Verify NO ACTION constant
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('NO ACTION', $constraintKey->getForeignKeyDeleteRule());

        // Verify RESTRICT constant
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyDeleteRule());

        // Verify SET DEFAULT constant
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_SET_DEFAULT);
        self::assertSame('SET DEFAULT', $constraintKey->getForeignKeyDeleteRule());
    }

    public function testCompleteConstraintKeyObject(): void
    {
        $constraintKey = new ConstraintKeyObject('user_id');

        $constraintKey->setOrdinalPosition(1)
            ->setPositionInUniqueConstraint(false)
            ->setReferencedTableSchema('public')
            ->setReferencedTableName('users')
            ->setReferencedColumnName('id');
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_CASCADE);
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_RESTRICT);

        // Verify all properties are set correctly
        self::assertSame('user_id', $constraintKey->getColumnName());
        self::assertSame(1, $constraintKey->getOrdinalPosition());
        self::assertFalse($constraintKey->getPositionInUniqueConstraint());
        self::assertSame('public', $constraintKey->getReferencedTableSchema());
        self::assertSame('users', $constraintKey->getReferencedTableName());
        self::assertSame('id', $constraintKey->getReferencedColumnName());
        self::assertSame('CASCADE', $constraintKey->getForeignKeyUpdateRule());
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyDeleteRule());
    }
}
