<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Source\TestAsset;

use DateTime;
use Override;
use PhpDb\Metadata\Source\AbstractSource;

/**
 * A concrete AbstractSource that seeds $this->data only when the matching loader runs.
 *
 * Tests that pre-seed $this->data by reflection cannot observe whether a getter still
 * calls its loader, because the data is already there. Loading on demand makes the call
 * load-bearing: drop it from the getter and the data is never there at all.
 *
 * Each loader delegates to its parent before seeding, which keeps the parent's protected
 * visibility observable from a subclass, and records the call so the caching contract can
 * be asserted directly.
 */
final class LazyRecordingSource extends AbstractSource
{
    public const string SCHEMA = 'public';

    public const string BASE_TABLE = 'users';

    public const string VIEW = 'active_users';

    public const string TRIGGER = 'users_audit';

    public const string PRIMARY_KEY = 'pk_users';

    public const string UNIQUE_KEY = 'uq_users_email';

    /** @var array<string, array<string, array<string, mixed>>> */
    private const array COLUMNS = [
        self::BASE_TABLE => [
            'id'    => [
                'ordinal_position'         => '1',
                'column_default'           => null,
                'is_nullable'              => false,
                'data_type'                => 'INT',
                'character_maximum_length' => null,
                'character_octet_length'   => null,
                'numeric_precision'        => '10',
                'numeric_scale'            => '2',
                'numeric_unsigned'         => true,
                'erratas'                  => ['storage' => 'inline'],
            ],
            'email' => [
                'ordinal_position'         => '2',
                'column_default'           => null,
                'is_nullable'              => true,
                'data_type'                => 'VARCHAR',
                'character_maximum_length' => '255',
                'character_octet_length'   => '1020',
                'numeric_precision'        => null,
                'numeric_scale'            => null,
                'numeric_unsigned'         => null,
                'erratas'                  => [],
            ],
        ],
        self::VIEW       => [
            'id' => [
                'ordinal_position'         => '1',
                'column_default'           => null,
                'is_nullable'              => false,
                'data_type'                => 'INT',
                'character_maximum_length' => null,
                'character_octet_length'   => null,
                'numeric_precision'        => null,
                'numeric_scale'            => null,
                'numeric_unsigned'         => null,
                'erratas'                  => [],
            ],
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private const array CONSTRAINTS = [
        self::BASE_TABLE => [
            self::PRIMARY_KEY => [
                'constraint_type' => 'PRIMARY KEY',
                'columns'         => ['id'],
            ],
            self::UNIQUE_KEY  => [
                'constraint_type' => 'UNIQUE',
                'columns'         => ['email'],
            ],
        ],
        self::VIEW       => [],
    ];

    /**
     * The first and last entries each match exactly one half of the table/constraint pair,
     * so a scan that accepts either half in place of both is observable. The single match
     * is preceded by a miss, so skipping a miss must not end the scan either.
     *
     * @var list<array<string, mixed>>
     */
    private const array CONSTRAINT_KEYS = [
        [
            'table_name'       => 'audit_log',
            'constraint_name'  => self::PRIMARY_KEY,
            'column_name'      => 'audit_id',
            'ordinal_position' => 1,
        ],
        [
            'table_name'       => self::BASE_TABLE,
            'constraint_name'  => self::PRIMARY_KEY,
            'column_name'      => 'id',
            'ordinal_position' => 1,
        ],
        [
            'table_name'       => self::BASE_TABLE,
            'constraint_name'  => self::UNIQUE_KEY,
            'column_name'      => 'email',
            'ordinal_position' => 1,
        ],
    ];

    /** @var list<array<string, string>> */
    private const array CONSTRAINT_REFERENCES = [
        [
            'constraint_name'        => self::PRIMARY_KEY,
            'update_rule'            => 'CASCADE',
            'delete_rule'            => 'RESTRICT',
            'referenced_table_name'  => 'accounts',
            'referenced_column_name' => 'account_id',
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private const array TABLE_NAMES = [
        self::BASE_TABLE => ['table_type' => 'BASE TABLE'],
        self::VIEW       => [
            'table_type'      => 'VIEW',
            'view_definition' => 'SELECT id FROM users',
            'check_option'    => 'CASCADED',
            'is_updatable'    => false,
        ],
    ];

    /** @var array<string, int> */
    private array $loadCounts = [];

    public function loadCount(string $loader): int
    {
        return $this->loadCounts[$loader] ?? 0;
    }

    /**
     * Exposes the protected hierarchy builder so its output shape can be asserted.
     */
    public function prepareHierarchy(string $type, string ...$keys): void
    {
        $this->prepareDataHierarchy($type, ...$keys);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function seededData(): array
    {
        return $this->data;
    }

    #[Override]
    protected function loadColumnData(string $table, string $schema): void
    {
        parent::loadColumnData($table, $schema);

        $this->seed('loadColumnData', $this->data['columns'][$schema][$table], self::COLUMNS[$table] ?? []);
    }

    #[Override]
    protected function loadConstraintData(string $table, string $schema): void
    {
        parent::loadConstraintData($table, $schema);

        $this->seed('loadConstraintData', $this->data['constraints'][$schema], self::CONSTRAINTS);
    }

    #[Override]
    protected function loadConstraintDataKeys(string $schema): void
    {
        parent::loadConstraintDataKeys($schema);

        $this->seed('loadConstraintDataKeys', $this->data['constraint_keys'][$schema], self::CONSTRAINT_KEYS);
    }

    #[Override]
    protected function loadConstraintReferences(string $table, string $schema): void
    {
        parent::loadConstraintReferences($table, $schema);

        $this->seed(
            'loadConstraintReferences',
            $this->data['constraint_references'][$schema],
            self::CONSTRAINT_REFERENCES,
        );
    }

    #[Override]
    protected function loadSchemaData(): void
    {
        $this->seed('loadSchemaData', $this->data['schemas'], [self::SCHEMA, 'reporting']);
    }

    #[Override]
    protected function loadTableNameData(string $schema): void
    {
        parent::loadTableNameData($schema);

        $this->seed('loadTableNameData', $this->data['table_names'][$schema], self::TABLE_NAMES);
    }

    #[Override]
    protected function loadTriggerData(string $schema): void
    {
        parent::loadTriggerData($schema);

        $this->seed('loadTriggerData', $this->data['triggers'][$schema], [
            self::TRIGGER => [
                'event_manipulation'         => 'INSERT',
                'event_object_catalog'       => 'catalog',
                'event_object_schema'        => $schema,
                'event_object_table'         => self::BASE_TABLE,
                'action_order'               => '1',
                'action_condition'           => null,
                'action_statement'           => 'INSERT INTO audit_log VALUES (NEW.id)',
                'action_orientation'         => 'ROW',
                'action_timing'              => 'AFTER',
                'action_reference_old_table' => null,
                'action_reference_new_table' => null,
                'action_reference_old_row'   => 'OLD',
                'action_reference_new_row'   => 'NEW',
                'created'                    => new DateTime('2026-01-01 00:00:00'),
            ],
        ]);
    }

    /**
     * Fills an empty slot once and records the call. A slot that already holds rows is
     * left alone, so repeated reads are served from whatever the first load put there.
     *
     * @param array<array-key, mixed>|null $slot
     * @param array<array-key, mixed>      $rows
     *
     * @param-out array<array-key, mixed> $slot
     */
    private function seed(string $loader, ?array &$slot, array $rows): void
    {
        if ([] !== ($slot ?? [])) {
            return;
        }

        $this->loadCounts[$loader] = $this->loadCount($loader) + 1;
        $slot                      = $rows;
    }
}
