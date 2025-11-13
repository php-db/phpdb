<?php

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\ConstraintKeyObject;
use PHPUnit\Framework\TestCase;

final class ConstraintKeyObjectTest extends TestCase
{
    public function testConstructorSetsColumnName(): void
    {
        $constraintKey = new ConstraintKeyObject('column_name');

        self::assertSame('column_name', $constraintKey->getColumnName());
    }

    public function testForeignKeyConstants(): void
    {
        self::assertSame('CASCADE', ConstraintKeyObject::FK_CASCADE);
        self::assertSame('SET NULL', ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('NO ACTION', ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('RESTRICT', ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('SET DEFAULT', ConstraintKeyObject::FK_SET_DEFAULT);
    }

    public function testSetColumnNameAndGetColumnNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('initial');
        $result = $constraintKey->setColumnName('new_column');

        self::assertSame($constraintKey, $result);
        self::assertSame('new_column', $constraintKey->getColumnName());
    }

    public function testSetOrdinalPositionAndGetOrdinalPositionWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $result = $constraintKey->setOrdinalPosition(3);

        self::assertSame($constraintKey, $result);
        self::assertSame(3, $constraintKey->getOrdinalPosition());
    }

    public function testSetPositionInUniqueConstraintAndGetPositionInUniqueConstraintWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $result = $constraintKey->setPositionInUniqueConstraint(true);

        self::assertSame($constraintKey, $result);
        self::assertTrue($constraintKey->getPositionInUniqueConstraint());
    }

    public function testSetReferencedTableSchemaAndGetReferencedTableSchemaWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $result = $constraintKey->setReferencedTableSchema('ref_schema');

        self::assertSame($constraintKey, $result);
        self::assertSame('ref_schema', $constraintKey->getReferencedTableSchema());
    }

    public function testSetReferencedTableNameAndGetReferencedTableNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $result = $constraintKey->setReferencedTableName('ref_table');

        self::assertSame($constraintKey, $result);
        self::assertSame('ref_table', $constraintKey->getReferencedTableName());
    }

    public function testSetReferencedColumnNameAndGetReferencedColumnNameWithFluentInterface(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $result = $constraintKey->setReferencedColumnName('ref_column');

        self::assertSame($constraintKey, $result);
        self::assertSame('ref_column', $constraintKey->getReferencedColumnName());
    }

    public function testSetForeignKeyUpdateRuleAndGetForeignKeyUpdateRule(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_CASCADE);

        self::assertSame('CASCADE', $constraintKey->getForeignKeyUpdateRule());
    }

    public function testSetForeignKeyUpdateRuleWithAllConstants(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyUpdateRule());

        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('SET NULL', $constraintKey->getForeignKeyUpdateRule());

        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('NO ACTION', $constraintKey->getForeignKeyUpdateRule());

        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyUpdateRule());

        $constraintKey->setForeignKeyUpdateRule(ConstraintKeyObject::FK_SET_DEFAULT);
        self::assertSame('SET DEFAULT', $constraintKey->getForeignKeyUpdateRule());
    }

    public function testSetForeignKeyDeleteRuleAndGetForeignKeyDeleteRule(): void
    {
        $constraintKey = new ConstraintKeyObject('column');
        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_RESTRICT);

        self::assertSame('RESTRICT', $constraintKey->getForeignKeyDeleteRule());
    }

    public function testSetForeignKeyDeleteRuleWithAllConstants(): void
    {
        $constraintKey = new ConstraintKeyObject('column');

        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_CASCADE);
        self::assertSame('CASCADE', $constraintKey->getForeignKeyDeleteRule());

        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_SET_NULL);
        self::assertSame('SET NULL', $constraintKey->getForeignKeyDeleteRule());

        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_NO_ACTION);
        self::assertSame('NO ACTION', $constraintKey->getForeignKeyDeleteRule());

        $constraintKey->setForeignKeyDeleteRule(ConstraintKeyObject::FK_RESTRICT);
        self::assertSame('RESTRICT', $constraintKey->getForeignKeyDeleteRule());

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