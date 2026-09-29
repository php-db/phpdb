<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifierFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversClass(TableIdentifierFactory::class)]
final class TableIdentifierFactoryTest extends TestCase
{
    public function testCallTimePrefixIsAppliedWhenNonePreconfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', null, 'archive');

        self::assertSame('archive', $tableIdentifier->getPrefix());
        self::assertSame('archive_users', $tableIdentifier->getTable());
    }

    public function testCallTimePrefixOverridesConfiguredPrefix(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users', null, 'archive');

        self::assertSame('archive', $tableIdentifier->getPrefix());
        self::assertSame('archive_users', $tableIdentifier->getTable());
    }

    public function testCallTimeSeparatorIsAppliedWhenNonePreconfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', null, 'archive', '__');

        self::assertSame('__', $tableIdentifier->getSeparator());
        self::assertSame('archive__users', $tableIdentifier->getTable());
    }

    public function testCallTimeSeparatorOverridesConfiguredSeparator(): void
    {
        $factory         = new TableIdentifierFactory('backup', '__');
        $tableIdentifier = $factory('users', null, null, '_');

        self::assertSame('_', $tableIdentifier->getSeparator());
        self::assertSame('backup_users', $tableIdentifier->getTable());
    }

    public function testConfiguredSeparatorIsCarriedButUnusedWhenNoPrefixApplies(): void
    {
        $factory         = new TableIdentifierFactory(null, '__');
        $tableIdentifier = $factory('users');

        self::assertSame('__', $tableIdentifier->getSeparator());
        self::assertNull($tableIdentifier->getPrefix());
        self::assertSame('users', $tableIdentifier->getTable());
    }

    public function testCreatesIdentifierWithConfiguredPrefix(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users');

        self::assertSame('backup', $tableIdentifier->getPrefix());
        self::assertSame('backup_users', $tableIdentifier->getTable());
        self::assertNull($tableIdentifier->getSchema());
    }

    public function testCreatesIdentifierWithConfiguredSeparator(): void
    {
        $factory         = new TableIdentifierFactory('backup', '__');
        $tableIdentifier = $factory('users');

        self::assertSame('__', $tableIdentifier->getSeparator());
        self::assertSame('backup__users', $tableIdentifier->getTable());
    }

    public function testCreatesIdentifierWithoutPrefixWhenNoneConfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', 'public');

        self::assertNull($tableIdentifier->getPrefix());
        self::assertSame('users', $tableIdentifier->getTable());
    }

    public function testCreatesIdentifierWithSchema(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users', 'public');

        self::assertSame(['backup_users', 'public'], $tableIdentifier->getTableAndSchema());
    }

    public function testPrefixIsNullByDefault(): void
    {
        $factory = new TableIdentifierFactory();

        self::assertNull($factory->getPrefix());
    }

    public function testRejectsEmptyStringCallTimePrefix(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_PREFIX);
        $factory('users', null, '');
    }

    public function testRejectsEmptyStringCallTimeSeparator(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        $factory('users', null, null, '');
    }

    public function testRejectsEmptyStringPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_PREFIX);
        new TableIdentifierFactory('');
    }

    public function testRejectsEmptyStringSeparator(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifierFactory('backup', '');
    }

    public function testRejectsEmptyStringSeparatorWithoutPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifierFactory(null, '');
    }

    public function testReturnsConfiguredPrefix(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::assertSame('backup', $factory->getPrefix());
    }

    public function testReturnsConfiguredSeparator(): void
    {
        $factory = new TableIdentifierFactory('backup', '__');

        self::assertSame('__', $factory->getSeparator());
    }

    public function testSeparatorDefaultsToUnderscore(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::assertSame('_', $factory->getSeparator());
    }

    public function testSeparatorDefaultsToUnderscoreWhenNoPrefixConfigured(): void
    {
        $factory = new TableIdentifierFactory();

        self::assertSame('_', $factory->getSeparator());
    }

    public function testSeparatorFallsBackToDefaultWhenPassedAsNull(): void
    {
        $factory = new TableIdentifierFactory('backup', null);

        self::assertSame('_', $factory->getSeparator());
    }
}
