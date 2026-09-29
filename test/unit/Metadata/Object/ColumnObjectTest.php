<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Object;

use PhpDb\Metadata\Object\ColumnObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnObjectTest extends TestCase
{
    #[Test]
    public function completeColumnObjectWithAllProperties(): void
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

        // Verify all properties are set correctly
        static::assertSame('id', $column->getName());
        static::assertSame('users', $column->getTableName());
        static::assertSame('public', $column->getSchemaName());
        static::assertSame(1, $column->getOrdinalPosition());
        static::assertSame('0', $column->getColumnDefault());
        static::assertFalse($column->getIsNullable());
        static::assertSame('INT', $column->getDataType());
        static::assertNull($column->getCharacterMaximumLength());
        static::assertNull($column->getCharacterOctetLength());
        static::assertSame(10, $column->getNumericPrecision());
        static::assertSame(0, $column->getNumericScale());
        static::assertTrue($column->isNumericUnsigned());
        static::assertTrue($column->getErrata('auto_increment'));
        static::assertSame('Primary key', $column->getErrata('comment'));
    }

    #[Test]
    public function constructorWithAllParameters(): void
    {
        $column = new ColumnObject('column_name', 'table_name', 'schema_name');

        // Verify all constructor parameters are set
        static::assertSame('column_name', $column->getName());
        static::assertSame('table_name', $column->getTableName());
        static::assertSame('schema_name', $column->getSchemaName());
    }

    #[Test]
    public function constructorWithNullSchema(): void
    {
        $column = new ColumnObject('column_name', 'table_name');

        // Verify schema defaults to null
        static::assertSame('column_name', $column->getName());
        static::assertSame('table_name', $column->getTableName());
        static::assertNull($column->getSchemaName());
    }

    #[Test]
    public function getErrataNonExistentKeyReturnsNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify non-existent key returns null
        static::assertNull($column->getErrata('non_existent'));
    }

    #[Test]
    public function getErratasReturnsEmptyArrayInitially(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify erratas default to empty array
        static::assertSame([], $column->getErratas());
    }

    #[Test]
    public function isNullableAlias(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify alias method returns same value
        $column->setIsNullable(false);
        static::assertFalse($column->getIsNullable());
    }

    #[Test]
    public function isNumericUnsignedAlias(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify alias method returns same value
        $column->setNumericUnsigned(false);
        static::assertFalse($column->isNumericUnsigned());
        static::assertSame($column->getNumericUnsigned(), $column->isNumericUnsigned());
    }

    #[Test]
    public function setCharacterMaximumLengthAndGetCharacterMaximumLengthWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setCharacterMaximumLength(255);
        static::assertSame($column, $result);
        static::assertSame(255, $column->getCharacterMaximumLength());
    }

    #[Test]
    public function setCharacterMaximumLengthWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setCharacterMaximumLength(255);

        // Set length to null and verify
        $column->setCharacterMaximumLength(null);
        static::assertNull($column->getCharacterMaximumLength());
    }

    #[Test]
    public function setCharacterOctetLengthAndGetCharacterOctetLengthWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setCharacterOctetLength(1024);
        static::assertSame($column, $result);
        static::assertSame(1024, $column->getCharacterOctetLength());
    }

    #[Test]
    public function setCharacterOctetLengthWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setCharacterOctetLength(1024);

        // Set octet length to null and verify
        $column->setCharacterOctetLength(null);
        static::assertNull($column->getCharacterOctetLength());
    }

    #[Test]
    public function setColumnDefaultAndGetColumnDefaultWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setColumnDefault('DEFAULT_VALUE');
        static::assertSame($column, $result);
        static::assertSame('DEFAULT_VALUE', $column->getColumnDefault());
    }

    #[Test]
    public function setColumnDefaultWithNull(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');
        $column->setColumnDefault('initial');

        // Set default to null and verify
        $column->setColumnDefault(null);
        static::assertNull($column->getColumnDefault());
    }

    #[Test]
    public function setDataTypeAndGetDataTypeWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setDataType('VARCHAR');
        static::assertSame($column, $result);
        static::assertSame('VARCHAR', $column->getDataType());
    }

    #[Test]
    public function setErrataAndGetErrata(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Set single errata and verify fluent interface
        $result = $column->setErrata('key1', 'value1');
        static::assertSame($column, $result);
        static::assertSame('value1', $column->getErrata('key1'));
    }

    #[Test]
    public function setErratasIteratesCorrectly(): void
    {
        $column  = new ColumnObject('column', 'table', 'schema');
        $erratas = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // Verify each errata is accessible individually
        $column->setErratas($erratas);
        static::assertSame('value1', $column->getErrata('key1'));
        static::assertSame('value2', $column->getErrata('key2'));
    }

    /**
     * Erratas accumulate: a second call adds to what is already there rather than
     * replacing it.
     */
    #[Test]
    public function setErratasMergesIntoTheExistingErrata(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        $column->setErratas(['auto_increment' => true]);
        $column->setErratas(['comment' => 'Primary key']);

        static::assertSame(
            ['auto_increment' => true, 'comment' => 'Primary key'],
            $column->getErratas(),
        );
    }

    #[Test]
    public function setErratasWithArrayAndGetErratas(): void
    {
        $column  = new ColumnObject('column', 'table', 'schema');
        $erratas = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // Set multiple erratas and verify fluent interface
        $result = $column->setErratas($erratas);
        static::assertSame($column, $result);
        static::assertSame($erratas, $column->getErratas());
    }

    #[Test]
    public function setIsNullableAndGetIsNullableWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setIsNullable(true);
        static::assertSame($column, $result);
        static::assertTrue($column->getIsNullable());
    }

    #[Test]
    public function setNameAndGetName(): void
    {
        $column = new ColumnObject('initial', 'table', 'schema');

        // Update name and verify change
        $column->setName('new_name');
        static::assertSame('new_name', $column->getName());
    }

    #[Test]
    public function setNumericPrecisionAndGetNumericPrecisionWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setNumericPrecision(10);
        static::assertSame($column, $result);
        static::assertSame(10, $column->getNumericPrecision());
    }

    #[Test]
    public function setNumericScaleAndGetNumericScaleWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setNumericScale(2);
        static::assertSame($column, $result);
        static::assertSame(2, $column->getNumericScale());
    }

    #[Test]
    public function setNumericUnsignedAndGetNumericUnsignedWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setNumericUnsigned(true);
        static::assertSame($column, $result);
        static::assertTrue($column->getNumericUnsigned());
    }

    #[Test]
    public function setOrdinalPositionAndGetOrdinalPositionWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setOrdinalPosition(5);
        static::assertSame($column, $result);
        static::assertSame(5, $column->getOrdinalPosition());
    }

    #[Test]
    public function setSchemaNameAndGetSchemaName(): void
    {
        $column = new ColumnObject('column', 'table', 'initial_schema');

        // Update schema and verify change
        $column->setSchemaName('new_schema');
        static::assertSame('new_schema', $column->getSchemaName());
    }

    #[Test]
    public function setTableNameAndGetTableNameWithFluentInterface(): void
    {
        $column = new ColumnObject('column', 'initial_table', 'schema');

        // Verify fluent interface and value update
        $result = $column->setTableName('new_table');
        static::assertSame($column, $result);
        static::assertSame('new_table', $column->getTableName());
    }
}
