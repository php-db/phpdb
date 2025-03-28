<?php

namespace LaminasTest\Db\Sql;

use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\TableIdentifier;
use LaminasTest\Db\TestAsset\ObjectToString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

use function array_merge;

/**
 * Tests for {@see TableIdentifier}
 */
#[CoversClass(TableIdentifier::class)]
class TableIdentifierTest extends TestCase
{
    public function testGetTable()
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertSame('foo', $tableIdentifier->getTable());
    }

    public function testGetDefaultSchema()
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertNull($tableIdentifier->getSchema());
    }

    public function testGetSchema()
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        self::assertSame('bar', $tableIdentifier->getSchema());
    }

    public function testGetTableFromObjectStringCast()
    {
        $table           = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier((string) $table);

        self::assertSame('castResult', $tableIdentifier->getTable());
        self::assertSame('castResult', $tableIdentifier->getTable());
    }

    public function testGetSchemaFromObjectStringCast()
    {
        $schema          = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier('foo', (string) $schema);

        self::assertSame('castResult', $tableIdentifier->getSchema());
        self::assertSame('castResult', $tableIdentifier->getSchema());
    }

    /**
     * @param mixed $invalidTable
     */
    #[DataProvider('invalidTableProvider')]
    public function testRejectsInvalidTable($invalidTable)
    {
        $this->expectException($invalidTable === '' ? InvalidArgumentException::class : TypeError::class);

        new TableIdentifier($invalidTable);
    }

    /**
     * @param mixed $invalidSchema
     */
    #[DataProvider('invalidSchemaProvider')]
    public function testRejectsInvalidSchema($invalidSchema)
    {
        $this->expectException($invalidSchema === '' ? InvalidArgumentException::class : TypeError::class);

        new TableIdentifier('foo', $invalidSchema);
    }

    /**
     * Data provider
     *
     * @return mixed[][]
     */
    public static function invalidTableProvider(): array
    {
        return array_merge(
            [[null]],
            self::invalidSchemaProvider()
        );
    }

    /**
     * Data provider
     *
     * @return mixed[][]
     */
    public static function invalidSchemaProvider(): array
    {
        return [
            [''],
            [new stdClass()],
            [[]],
        ];
    }
}
