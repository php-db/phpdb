<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\TestAsset\ObjectToString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function getDefaultPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        static::assertNull($tableIdentifier->getPrefix());
    }

    #[Test]
    public function getDefaultSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        static::assertNull($tableIdentifier->getSchema());
    }

    #[Test]
    public function getDefaultSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        static::assertSame('_', $tableIdentifier->getSeparator());
    }

    #[Test]
    public function getPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        static::assertSame('backup', $tableIdentifier->getPrefix());
    }

    #[Test]
    public function getSchema(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        static::assertSame('bar', $tableIdentifier->getSchema());
    }

    /**
     * @todo Review test to see if relevant?
     */
    #[Test]
    public function getSchemaFromObjectStringCast(): void
    {
        $schema          = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier('foo', (string) $schema);

        static::assertSame('castResult', $tableIdentifier->getSchema());
        static::assertSame('castResult', $tableIdentifier->getSchema());
    }

    #[Test]
    public function getSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup', '__');

        static::assertSame('__', $tableIdentifier->getSeparator());
    }

    #[Test]
    public function getTable(): void
    {
        $tableIdentifier = new TableIdentifier('foo');

        static::assertSame('foo', $tableIdentifier->getTable());
    }

    #[Test]
    public function getTableAndSchemaAppliesPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar', 'backup');

        static::assertSame(['backup_foo', 'bar'], $tableIdentifier->getTableAndSchema());
    }

    #[Test]
    public function getTableAndSchemaWithoutPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');

        static::assertSame(['foo', 'bar'], $tableIdentifier->getTableAndSchema());
    }

    #[Test]
    public function getTableAppliesPrefixWithCustomSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup', '__');

        static::assertSame('backup__foo', $tableIdentifier->getTable());
    }

    #[Test]
    public function getTableAppliesPrefixWithDefaultSeparator(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        static::assertSame('backup_foo', $tableIdentifier->getTable());
    }

    #[Test]
    public function getTableFromObjectStringCast(): void
    {
        $table           = new ObjectToString('castResult');
        $tableIdentifier = new TableIdentifier((string) $table);

        static::assertSame('castResult', $tableIdentifier->getTable());
        static::assertSame('castResult', $tableIdentifier->getTable());
    }

    #[Test]
    public function getTableIgnoresSeparatorWithoutPrefix(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, null, '__');

        static::assertSame('foo', $tableIdentifier->getTable());
    }

    #[Test]
    public function getUnprefixedTableReturnsTableAsGiven(): void
    {
        $tableIdentifier = new TableIdentifier('foo', null, 'backup');

        static::assertSame('foo', $tableIdentifier->getUnprefixedTable());
    }

    #[Test]
    public function rejectsEmptyStringSeparatorWithoutPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifier('foo', null, null, '');
    }

    #[Test]
    #[DataProvider('invalidNameArgumentProvider')]
    public function rejectsInvalidPrefix(mixed $invalidPrefix): void
    {
        self::expectException('' === $invalidPrefix ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', 'bar', $invalidPrefix);
    }

    #[Test]
    #[DataProvider('invalidNameArgumentProvider')]
    public function rejectsInvalidSchema(mixed $invalidSchema): void
    {
        self::expectException('' === $invalidSchema ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', $invalidSchema);
    }

    #[Test]
    #[DataProvider('invalidNameArgumentProvider')]
    public function rejectsInvalidSeparator(mixed $invalidSeparator): void
    {
        self::expectException('' === $invalidSeparator ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier('foo', 'bar', 'backup', $invalidSeparator);
    }

    #[Test]
    #[DataProvider('invalidTableProvider')]
    public function rejectsInvalidTable(mixed $invalidTable): void
    {
        self::expectException('' === $invalidTable ? InvalidArgumentException::class : TypeError::class);
        /** @psalm-suppress MixedArgument */
        new TableIdentifier($invalidTable);
    }
}
