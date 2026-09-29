<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\ObjectToString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

/**
 * Tests for {@see TableIdentifier}
 */
#[CoversClass(TableIdentifier::class)]
#[Group('unit')]
class TableIdentifierTest extends TestCase
{
    /**
     * Data provider
     *
     * @return array[]
     */
    public static function invalidNameArgumentProvider(): array
    {
        return [
            'empty string' => [''],
            'object'       => [new stdClass()],
            'array'        => [[]],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public static function invalidTableProvider(): array
    {
        return [
            'null' => [null],
            ...self::invalidNameArgumentProvider(),
        ];
    }

    public function testGetDefaultPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertNull($tableIdentifier->getPrefix());
    }

    public function testGetDefaultSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertNull($tableIdentifier->getSchema());
    }

    public function testGetDefaultSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertSame('_', $tableIdentifier->getSeparator());
    }

    public function testGetPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        self::assertSame('backup', $tableIdentifier->getPrefix());
    }

    public function testGetSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        self::assertSame('bar', $tableIdentifier->getSchema());
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

    public function testGetSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup', '__');

        self::assertSame('__', $tableIdentifier->getSeparator());
    }

    public function testGetTable(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        self::assertSame('foo', $tableIdentifier->getTable());
    }

    public function testGetTableAndSchemaAppliesPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar', 'backup');

        self::assertSame(['backup_foo', 'bar'], $tableIdentifier->getTableAndSchema());
    }

    public function testGetTableAndSchemaWithoutPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        self::assertSame(['foo', 'bar'], $tableIdentifier->getTableAndSchema());
    }

    public function testGetTableAppliesPrefixWithCustomSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup', '__');

        self::assertSame('backup__foo', $tableIdentifier->getTable());
    }

    public function testGetTableAppliesPrefixWithDefaultSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        self::assertSame('backup_foo', $tableIdentifier->getTable());
    }

    public function testGetTableFromObjectStringCast(): void
    {
        $table           = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier((string) $table);

        self::assertSame('castResult', $tableIdentifier->getTable());
        self::assertSame('castResult', $tableIdentifier->getTable());
    }

    public function testGetTableIgnoresSeparatorWithoutPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, null, '__');

        self::assertSame('foo', $tableIdentifier->getTable());
    }

    public function testGetUnprefixedTableReturnsTableAsGiven(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        self::assertSame('foo', $tableIdentifier->getUnprefixedTable());
    }

    public function testRejectsEmptyStringSeparatorWithoutPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifier('foo', null, null, '');
    }

    #[DataProvider('invalidNameArgumentProvider')]
    public function testRejectsInvalidPrefix(mixed $invalidPrefix): void
    {
        self::expectException('' === $invalidPrefix ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', 'bar', $invalidPrefix);
    }

    #[DataProvider('invalidNameArgumentProvider')]
    public function testRejectsInvalidSchema(mixed $invalidSchema): void
    {
        self::expectException('' === $invalidSchema ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', $invalidSchema);
    }

    #[DataProvider('invalidNameArgumentProvider')]
    public function testRejectsInvalidSeparator(mixed $invalidSeparator): void
    {
        self::expectException('' === $invalidSeparator ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', 'bar', 'backup', $invalidSeparator);
    }

    #[DataProvider('invalidTableProvider')]
    public function testRejectsInvalidTable(mixed $invalidTable): void
    {
        self::expectException('' === $invalidTable ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier($invalidTable);
    }
}
