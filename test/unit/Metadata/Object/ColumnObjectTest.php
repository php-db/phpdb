<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\ColumnObject;
use PHPUnit\Framework\TestCase;

final class ColumnObjectTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $column = new ColumnObject('column_name', 'table_name', 'schema_name');

        self::assertSame('column_name', $column->getName());
        self::assertSame('table_name', $column->getTableName());
        self::assertSame('schema_name', $column->getSchemaName());
    }

    public function testConstructorWithNullSchema(): void
    {
        $column = new ColumnObject('column_name', 'table_name');

        self::assertSame('column_name', $column->getName());
        self::assertSame('table_name', $column->getTableName());
        self::assertNull($column->getSchemaName());
    }

    public function testSetNameAndGetName(): void
    {
        $column = new ColumnObject('initial', 'table', 'schema');
        $column->setName('new_name');

        self::assertSame('new_name', $column->getName());
    }

    public function testSetTableNameAndGetTableNameWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'initial_table', 'schema');
        $result = $column->setTableName('new_table');

        self::assertSame($column, $result);
        self::assertSame('new_table', $column->getTableName());
    }

    public function testSetSchemaNameAndGetSchemaName(): void
    {
        $column = new ColumnObject('column', 'table', 'initial_schema');
        $column->setSchemaName('new_schema');

        self::assertSame('new_schema', $column->getSchemaName());
    }

    public function testSetOrdinalPositionAndGetOrdinalPositionWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setOrdinalPosition(5);

        self::assertSame($column, $result);
        self::assertSame(5, $column->getOrdinalPosition());
    }

    public function testSetColumnDefaultAndGetColumnDefaultWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setColumnDefault('DEFAULT_VALUE');

        self::assertSame($column, $result);
        self::assertSame('DEFAULT_VALUE', $column->getColumnDefault());
    }

    public function testSetColumnDefaultWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setColumnDefault('initial');
        $column->setColumnDefault(null);

        self::assertNull($column->getColumnDefault());
    }

    public function testSetIsNullableAndGetIsNullableWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setIsNullable(true);

        self::assertSame($column, $result);
        self::assertTrue($column->getIsNullable());
    }

    public function testIsNullableAlias(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setIsNullable(false);

        self::assertFalse($column->isNullable());
        self::assertSame($column->getIsNullable(), $column->isNullable());
    }

    public function testSetDataTypeAndGetDataTypeWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setDataType('VARCHAR');

        self::assertSame($column, $result);
        self::assertSame('VARCHAR', $column->getDataType());
    }

    public function testSetCharacterMaximumLengthAndGetCharacterMaximumLengthWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setCharacterMaximumLength(255);

        self::assertSame($column, $result);
        self::assertSame(255, $column->getCharacterMaximumLength());
    }

    public function testSetCharacterMaximumLengthWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setCharacterMaximumLength(255);
        $column->setCharacterMaximumLength(null);

        self::assertNull($column->getCharacterMaximumLength());
    }

    public function testSetCharacterOctetLengthAndGetCharacterOctetLengthWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setCharacterOctetLength(1024);

        self::assertSame($column, $result);
        self::assertSame(1024, $column->getCharacterOctetLength());
    }

    public function testSetCharacterOctetLengthWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setCharacterOctetLength(1024);
        $column->setCharacterOctetLength(null);

        self::assertNull($column->getCharacterOctetLength());
    }

    public function testSetNumericPrecisionAndGetNumericPrecisionWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setNumericPrecision(10);

        self::assertSame($column, $result);
        self::assertSame(10, $column->getNumericPrecision());
    }

    public function testSetNumericScaleAndGetNumericScaleWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setNumericScale(2);

        self::assertSame($column, $result);
        self::assertSame(2, $column->getNumericScale());
    }

    public function testSetNumericUnsignedAndGetNumericUnsignedWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setNumericUnsigned(true);

        self::assertSame($column, $result);
        self::assertTrue($column->getNumericUnsigned());
    }

    public function testIsNumericUnsignedAlias(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setNumericUnsigned(false);

        self::assertFalse($column->isNumericUnsigned());
        self::assertSame($column->getNumericUnsigned(), $column->isNumericUnsigned());
    }

    public function testSetErrataAndGetErrata(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $result = $column->setErrata('key1', 'value1');

        self::assertSame($column, $result);
        self::assertSame('value1', $column->getErrata('key1'));
    }

    public function testGetErrataNonExistentKeyReturnsNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        self::assertNull($column->getErrata('non_existent'));
    }

    public function testSetErratasWithArrayAndGetErratas(): void
    {
        $column  = new ColumnObject('column', 'table', 'schema');
        $erratas = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $result  = $column->setErratas($erratas);

        self::assertSame($column, $result);
        self::assertSame($erratas, $column->getErratas());
    }

    public function testSetErratasIteratesCorrectly(): void
    {
        $column  = new ColumnObject('column', 'table', 'schema');
        $erratas = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $column->setErratas($erratas);

        self::assertSame('value1', $column->getErrata('key1'));
        self::assertSame('value2', $column->getErrata('key2'));
    }

    public function testGetErratasReturnsEmptyArrayInitially(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        self::assertSame([], $column->getErratas());
    }

    public function testCompleteColumnObjectWithAllProperties(): void
    {
        $column = new ColumnObject('id', 'users', 'public');
        $column->setOrdinalPosition(1)
            ->setColumnDefault('0')
            ->setIsNullable(false)
            ->setDataType('INT')
            ->setCharacterMaximumLength(null)
            ->setCharacterOctetLength(null)
            ->setNumericPrecision(10)
            ->setNumericScale(0)
            ->setNumericUnsigned(true)
            ->setErratas(['auto_increment' => true, 'comment' => 'Primary key']);

        self::assertSame('id', $column->getName());
        self::assertSame('users', $column->getTableName());
        self::assertSame('public', $column->getSchemaName());
        self::assertSame(1, $column->getOrdinalPosition());
        self::assertSame('0', $column->getColumnDefault());
        self::assertFalse($column->isNullable());
        self::assertSame('INT', $column->getDataType());
        self::assertNull($column->getCharacterMaximumLength());
        self::assertNull($column->getCharacterOctetLength());
        self::assertSame(10, $column->getNumericPrecision());
        self::assertSame(0, $column->getNumericScale());
        self::assertTrue($column->isNumericUnsigned());
        self::assertTrue($column->getErrata('auto_increment'));
        self::assertSame('Primary key', $column->getErrata('comment'));
    }
}
