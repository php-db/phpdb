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
    #[\PHPUnit\Framework\Attributes\Test]
    public function callTimePrefixIsAppliedWhenNonePreconfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', null, 'archive');

        static::assertSame('archive', $tableIdentifier->getPrefix());
        static::assertSame('archive_users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callTimePrefixOverridesConfiguredPrefix(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users', null, 'archive');

        static::assertSame('archive', $tableIdentifier->getPrefix());
        static::assertSame('archive_users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callTimeSeparatorIsAppliedWhenNonePreconfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', null, 'archive', '__');

        static::assertSame('__', $tableIdentifier->getSeparator());
        static::assertSame('archive__users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function callTimeSeparatorOverridesConfiguredSeparator(): void
    {
        $factory         = new TableIdentifierFactory('backup', '__');
        $tableIdentifier = $factory('users', null, null, '_');

        static::assertSame('_', $tableIdentifier->getSeparator());
        static::assertSame('backup_users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function configuredSeparatorIsCarriedButUnusedWhenNoPrefixApplies(): void
    {
        $factory         = new TableIdentifierFactory(null, '__');
        $tableIdentifier = $factory('users');

        static::assertSame('__', $tableIdentifier->getSeparator());
        static::assertNull($tableIdentifier->getPrefix());
        static::assertSame('users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createsIdentifierWithConfiguredPrefix(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users');

        static::assertSame('backup', $tableIdentifier->getPrefix());
        static::assertSame('backup_users', $tableIdentifier->getTable());
        static::assertNull($tableIdentifier->getSchema());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createsIdentifierWithConfiguredSeparator(): void
    {
        $factory         = new TableIdentifierFactory('backup', '__');
        $tableIdentifier = $factory('users');

        static::assertSame('__', $tableIdentifier->getSeparator());
        static::assertSame('backup__users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createsIdentifierWithoutPrefixWhenNoneConfigured(): void
    {
        $factory         = new TableIdentifierFactory();
        $tableIdentifier = $factory('users', 'public');

        static::assertNull($tableIdentifier->getPrefix());
        static::assertSame('users', $tableIdentifier->getTable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createsIdentifierWithSchema(): void
    {
        $factory         = new TableIdentifierFactory('backup');
        $tableIdentifier = $factory('users', 'public');

        static::assertSame(['backup_users', 'public'], $tableIdentifier->getTableAndSchema());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prefixIsNullByDefault(): void
    {
        $factory = new TableIdentifierFactory();

        static::assertNull($factory->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectsEmptyStringCallTimePrefix(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_PREFIX);
        $factory('users', null, '');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectsEmptyStringCallTimeSeparator(): void
    {
        $factory = new TableIdentifierFactory('backup');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        $factory('users', null, null, '');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectsEmptyStringPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_PREFIX);
        new TableIdentifierFactory('');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectsEmptyStringSeparator(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifierFactory('backup', '');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejectsEmptyStringSeparatorWithoutPrefix(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_SEPARATOR);
        new TableIdentifierFactory(null, '');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function returnsConfiguredPrefix(): void
    {
        $factory = new TableIdentifierFactory('backup');

        static::assertSame('backup', $factory->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function returnsConfiguredSeparator(): void
    {
        $factory = new TableIdentifierFactory('backup', '__');

        static::assertSame('__', $factory->getSeparator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function separatorDefaultsToUnderscore(): void
    {
        $factory = new TableIdentifierFactory('backup');

        static::assertSame('_', $factory->getSeparator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function separatorDefaultsToUnderscoreWhenNoPrefixConfigured(): void
    {
        $factory = new TableIdentifierFactory();

        static::assertSame('_', $factory->getSeparator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function separatorFallsBackToDefaultWhenPassedAsNull(): void
    {
        $factory = new TableIdentifierFactory('backup', null);

        static::assertSame('_', $factory->getSeparator());
    }
}
