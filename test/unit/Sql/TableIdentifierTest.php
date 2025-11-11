<?php

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\ObjectToString;
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
    public function testGetTable(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertSame('foo', $tableIdentifier->getTable());
    }

    public function testGetDefaultSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertNull($tableIdentifier->getSchema());
    }

    public function testGetSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        self::assertSame('bar', $tableIdentifier->getSchema());
    }

    public function testGetTableFromObjectStringCast(): void
    {
        $table           = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier((string) $table);

        self::assertSame('castResult', $tableIdentifier->getTable());
        self::assertSame('castResult', $tableIdentifier->getTable());
    }

    /**
     * @todo Review test to see if relevant?
     */
    public function testGetSchemaFromObjectStringCast(): void
    {
        $schema          = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier('foo', (string) $schema);

        self::assertSame('castResult', $tableIdentifier->getSchema());
        self::assertSame('castResult', $tableIdentifier->getSchema());
    }

    #[DataProvider('invalidTableProvider')]
    public function testRejectsInvalidTable(mixed $invalidTable): void
    {
        $this->expectException($invalidTable === '' ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier($invalidTable);
    }

    #[DataProvider('invalidSchemaProvider')]
    public function testRejectsInvalidSchema(mixed $invalidSchema): void
    {
        $this->expectException($invalidSchema === '' ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', $invalidSchema);
    }

    /**
     * Data provider
     *
     * @return array[]
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
     * @return array[]
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
