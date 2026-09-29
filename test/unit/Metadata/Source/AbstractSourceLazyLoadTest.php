<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Source;

use DateTime;
use Exception;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\Metadata\Source\AbstractSource;
use PhpDbTest\Metadata\Source\TestAsset\LazyRecordingSource;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * Exercises AbstractSource against a source that loads on demand.
 *
 * AbstractSourceTest seeds the data property by reflection before calling a getter, which
 * makes the getter's own loader call unobservable. These cases drive a source that seeds
 * nothing until the matching loader runs, so each getter is held to actually calling it,
 * and to transferring every field it reads onto the object it returns.
 */
#[CoversMethod(AbstractSource::class, 'getColumn')]
#[CoversMethod(AbstractSource::class, 'getColumnNames')]
#[CoversMethod(AbstractSource::class, 'getColumns')]
#[CoversMethod(AbstractSource::class, 'getConstraint')]
#[CoversMethod(AbstractSource::class, 'getConstraintKeys')]
#[CoversMethod(AbstractSource::class, 'getConstraints')]
#[CoversMethod(AbstractSource::class, 'getTable')]
#[CoversMethod(AbstractSource::class, 'getTableNames')]
#[CoversMethod(AbstractSource::class, 'getTables')]
#[CoversMethod(AbstractSource::class, 'getTrigger')]
#[CoversMethod(AbstractSource::class, 'getTriggerNames')]
#[CoversMethod(AbstractSource::class, 'getView')]
#[CoversMethod(AbstractSource::class, 'getViewNames')]
#[CoversMethod(AbstractSource::class, 'loadColumnData')]
#[CoversMethod(AbstractSource::class, 'loadConstraintData')]
#[CoversMethod(AbstractSource::class, 'loadConstraintDataKeys')]
#[CoversMethod(AbstractSource::class, 'loadConstraintReferences')]
#[CoversMethod(AbstractSource::class, 'loadTableNameData')]
#[CoversMethod(AbstractSource::class, 'loadTriggerData')]
#[CoversMethod(AbstractSource::class, 'prepareDataHierarchy')]
#[Group('unit')]
final class AbstractSourceLazyLoadTest extends TestCase
{
    private LazyRecordingSource $source;

    /**
     * @throws Exception
     */
    #[Test]
    public function getColumnLoadsColumnDataOnDemand(): void
    {
        $column = $this->source->getColumn('id', LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertSame('id', $column->getName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getColumnNamesLoadsColumnDataOnDemand(): void
    {
        $names = $this->source->getColumnNames(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertSame(['id', 'email'], $names);
    }

    /**
     * Every attribute the loader supplies must reach the ColumnObject, not just the ones
     * a narrower assertion happens to look at.
     *
     * @throws Exception
     */
    #[Test]
    public function getColumnTransfersEveryAttributeFromTheLoadedRow(): void
    {
        $column = $this->source->getColumn('id', LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertSame(
            [
                'ordinal_position'         => 1,
                'column_default'           => null,
                'is_nullable'              => false,
                'data_type'                => 'INT',
                'character_maximum_length' => null,
                'character_octet_length'   => null,
                'numeric_precision'        => 10,
                'numeric_scale'            => 2,
                'numeric_unsigned'         => true,
                'erratas'                  => ['storage' => 'inline'],
            ],
            [
                'ordinal_position'         => $column->getOrdinalPosition(),
                'column_default'           => $column->getColumnDefault(),
                'is_nullable'              => $column->getIsNullable(),
                'data_type'                => $column->getDataType(),
                'character_maximum_length' => $column->getCharacterMaximumLength(),
                'character_octet_length'   => $column->getCharacterOctetLength(),
                'numeric_precision'        => $column->getNumericPrecision(),
                'numeric_scale'            => $column->getNumericScale(),
                'numeric_unsigned'         => $column->getNumericUnsigned(),
                'erratas'                  => $column->getErratas(),
            ],
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getConstraintKeysLoadsConstraintKeyDataOnDemand(): void
    {
        $keys = $this->source->getConstraintKeys(
            LazyRecordingSource::PRIMARY_KEY,
            LazyRecordingSource::BASE_TABLE,
            LazyRecordingSource::SCHEMA,
        );

        static::assertCount(1, $keys);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getConstraintKeysLoadsConstraintReferencesOnDemand(): void
    {
        $keys = $this->source->getConstraintKeys(
            LazyRecordingSource::PRIMARY_KEY,
            LazyRecordingSource::BASE_TABLE,
            LazyRecordingSource::SCHEMA,
        );

        static::assertSame('accounts', $keys[0]->getReferencedTableName());
    }

    /**
     * The fixture holds one key matching only the table name and one matching only the
     * constraint name, so accepting either in place of both is observable.
     *
     * @throws Exception
     */
    #[Test]
    public function getConstraintKeysRequiresBothTableAndConstraintToMatch(): void
    {
        $keys = $this->source->getConstraintKeys(
            LazyRecordingSource::PRIMARY_KEY,
            LazyRecordingSource::BASE_TABLE,
            LazyRecordingSource::SCHEMA,
        );

        static::assertSame(['id'], array_map(static fn($key): string => $key->getColumnName(), $keys));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getConstraintLoadsConstraintDataOnDemand(): void
    {
        $constraint = $this->source->getConstraint(
            LazyRecordingSource::PRIMARY_KEY,
            LazyRecordingSource::BASE_TABLE,
            LazyRecordingSource::SCHEMA,
        );

        static::assertSame('PRIMARY KEY', $constraint->getType());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getConstraintsLoadsConstraintDataOnDemand(): void
    {
        $constraints = $this->source->getConstraints(
            LazyRecordingSource::BASE_TABLE,
            LazyRecordingSource::SCHEMA,
        );

        static::assertSame(
            [LazyRecordingSource::PRIMARY_KEY, LazyRecordingSource::UNIQUE_KEY],
            array_map(static fn($constraint): string => $constraint->getName(), $constraints),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTableLoadsTableNameDataOnDemand(): void
    {
        $table = $this->source->getTable(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertInstanceOf(TableObject::class, $table);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTableNamesLoadsTableNameDataOnDemand(): void
    {
        $names = $this->source->getTableNames(LazyRecordingSource::SCHEMA, true);

        static::assertSame([LazyRecordingSource::BASE_TABLE, LazyRecordingSource::VIEW], $names);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTablePopulatesColumnsOnTheReturnedTable(): void
    {
        $table = $this->source->getTable(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertCount(2, $table->getColumns());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTablePopulatesConstraintsOnTheReturnedTable(): void
    {
        $table = $this->source->getTable(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertCount(2, $table->getConstraints());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTablesExcludesViewsUnlessAsked(): void
    {
        $tables = $this->source->getTables(LazyRecordingSource::SCHEMA);

        static::assertSame(
            [LazyRecordingSource::BASE_TABLE],
            array_map(static fn($table): string => $table->getName(), $tables),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTriggerLoadsTriggerDataOnDemand(): void
    {
        $trigger = $this->source->getTrigger(LazyRecordingSource::TRIGGER, LazyRecordingSource::SCHEMA);

        static::assertSame(LazyRecordingSource::TRIGGER, $trigger->getName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTriggerNamesLoadsTriggerDataOnDemand(): void
    {
        $names = $this->source->getTriggerNames(LazyRecordingSource::SCHEMA);

        static::assertSame([LazyRecordingSource::TRIGGER], $names);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getTriggerTransfersTheCreatedTimestamp(): void
    {
        $trigger = $this->source->getTrigger(LazyRecordingSource::TRIGGER, LazyRecordingSource::SCHEMA);

        static::assertEquals(new DateTime('2026-01-01 00:00:00'), $trigger->getCreated());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getViewLoadsTableNameDataOnDemand(): void
    {
        $view = $this->source->getView(LazyRecordingSource::VIEW, LazyRecordingSource::SCHEMA);

        static::assertInstanceOf(ViewObject::class, $view);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getViewNamesLoadsTableNameDataOnDemand(): void
    {
        $names = $this->source->getViewNames(LazyRecordingSource::SCHEMA);

        static::assertSame([LazyRecordingSource::VIEW], $names);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function loadersRunOnceAcrossRepeatedReads(): void
    {
        $this->source->getColumnNames(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);
        $this->source->getColumnNames(LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);
        $this->source->getColumn('id', LazyRecordingSource::BASE_TABLE, LazyRecordingSource::SCHEMA);

        static::assertSame(1, $this->source->loadCount('loadColumnData'));
    }

    /**
     * assertArrayHasKey passes for a null value, so the hierarchy is compared whole:
     * every level must be an array, not an auto-vivified null.
     */
    #[Test]
    public function prepareDataHierarchyCreatesAnArrayAtEveryLevel(): void
    {
        $this->source->prepareHierarchy('level1', 'level2', 'level3');

        static::assertSame(['level1' => ['level2' => ['level3' => []]]], $this->source->seededData());
    }

    #[Override]
    protected function setUp(): void
    {
        $adapter = $this->createMockForIntersectionOfInterfaces([
            AdapterInterface::class,
            SchemaAwareInterface::class,
        ]);
        $adapter->method('getCurrentSchema')->willReturn(LazyRecordingSource::SCHEMA);

        $this->source = new LazyRecordingSource($adapter);
    }
}
