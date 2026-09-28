<?php

declare(strict_types=1);

namespace PhpDb\Metadata\Source;

use DateTime;
use Exception;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintKeyObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\TriggerObject;
use PhpDb\Metadata\Object\ViewObject;

use function array_keys;

/**
 * AbstractSource
 *
 * @psalm-type MetadataTableNameInfo = array{
 *     table_type: string,
 *     view_definition?: string|null,
 *     check_option?: string|null,
 *     is_updatable?: bool|null,
 * }
 * @psalm-type MetadataColumnInfo = array{
 *     ordinal_position: int|string|null,
 *     column_default: string|int|bool|null,
 *     is_nullable: bool|null,
 *     data_type: string,
 *     character_maximum_length: int|string|null,
 *     character_octet_length: int|string|null,
 *     numeric_precision: int|string|null,
 *     numeric_scale: int|string|null,
 *     numeric_unsigned: bool|null,
 *     erratas: array<string, mixed>,
 * }
 * @psalm-type MetadataConstraintInfo = array{
 *     constraint_type?: string,
 *     match_option?: string,
 *     update_rule?: string,
 *     delete_rule?: string,
 *     columns?: list<string>,
 *     referenced_table_schema?: string,
 *     referenced_table_name?: string,
 *     referenced_columns?: list<string>,
 *     check_clause?: string,
 * }
 * @psalm-type MetadataConstraintKeyInfo = array{
 *     table_name: string,
 *     constraint_name: string,
 *     column_name: string,
 *     ordinal_position: int,
 * }
 * @psalm-type MetadataConstraintReferenceInfo = array{
 *     constraint_name: string,
 *     update_rule: string,
 *     delete_rule: string,
 *     referenced_table_name: string,
 *     referenced_column_name: string,
 * }
 * @psalm-type MetadataTriggerInfo = array{
 *     event_manipulation: string,
 *     event_object_catalog: string,
 *     event_object_schema: string,
 *     event_object_table: string,
 *     action_order: string,
 *     action_condition: string|null,
 *     action_statement: string,
 *     action_orientation: string,
 *     action_timing: string,
 *     action_reference_old_table: string|null,
 *     action_reference_new_table: string|null,
 *     action_reference_old_row: string,
 *     action_reference_new_row: string,
 *     created: DateTime|null,
 * }
 * @psalm-type MetadataData = array{
 *     schemas?: list<string>,
 *     table_names?: array<string, array<string, MetadataTableNameInfo>>,
 *     columns?: array<string, array<string, array<string, MetadataColumnInfo>>>,
 *     constraints?: array<string, array<string, array<string, MetadataConstraintInfo>>>,
 *     constraint_keys?: array<string, list<MetadataConstraintKeyInfo>>,
 *     constraint_references?: array<string, list<MetadataConstraintReferenceInfo>>,
 *     triggers?: array<string, array<string, MetadataTriggerInfo>>,
 * }
 *
 * @api
 *
 * @mago-expect lint:too-many-methods
 * @mago-expect lint:kan-defect
 * @mago-expect lint:cyclomatic-complexity
 */
abstract class AbstractSource implements MetadataInterface
{
    public const DEFAULT_SCHEMA = '__DEFAULT_SCHEMA__';

    protected string $defaultSchema;

    /** @var MetadataData */
    protected array $data = [];

    public function __construct(
        protected AdapterInterface&SchemaAwareInterface $adapter,
    ) {
        $currentSchema       = $this->adapter->getCurrentSchema();
        $this->defaultSchema = false !== $currentSchema && '' !== $currentSchema
            ? $currentSchema
            : self::DEFAULT_SCHEMA;
    }

    /**
     * @throws Exception If the column does not exist.
     */
    #[Override]
    public function getColumn(string $columnName, string $table, ?string $schema = null): ColumnObject
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadColumnData($table, $schema);

        $info = $this->data['columns'][$schema][$table][$columnName] ?? null;
        if (null === $info) {
            throw new Exception('A column by that name was not found.');
        }

        $column = new ColumnObject($columnName, $table, $schema);
        $column->setOrdinalPosition($info['ordinal_position'] ? (int) $info['ordinal_position'] : null);
        $column->setColumnDefault($info['column_default']);
        $column->setIsNullable($info['is_nullable']);
        $column->setDataType($info['data_type']);
        $column->setCharacterMaximumLength(
            $info['character_maximum_length'] ? (int) $info['character_maximum_length'] : null,
        );
        $column->setCharacterOctetLength(
            $info['character_octet_length'] ? (int) $info['character_octet_length'] : null,
        );
        $column->setNumericPrecision(
            $info['numeric_precision'] ? (int) $info['numeric_precision'] : null,
        );
        $column->setNumericScale(
            $info['numeric_scale'] ? (int) $info['numeric_scale'] : null,
        );
        $column->setNumericUnsigned($info['numeric_unsigned']);
        $column->setErratas($info['erratas']);

        return $column;
    }

    /**
     * @return list<string>
     *
     * @throws Exception If the table does not exist.
     */
    #[Override]
    public function getColumnNames(string $table, ?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadColumnData($table, $schema);

        $columns = $this->data['columns'][$schema][$table] ?? null;
        if (null === $columns) {
            throw new Exception("\"{$table}\" does not exist");
        }

        return array_keys($columns);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<ColumnObject>
     *
     * @throws Exception If the table does not exist.
     */
    #[Override]
    public function getColumns(string $table, ?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadColumnData($table, $schema);

        $columns = [];
        foreach ($this->getColumnNames($table, $schema) as $columnName) {
            $columns[] = $this->getColumn($columnName, $table, $schema);
        }

        return $columns;
    }

    /**
     * @throws Exception If the constraint does not exist.
     */
    #[Override]
    public function getConstraint(
        string $constraintName,
        string $table,
        ?string $schema = null,
    ): ConstraintObject {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadConstraintData($table, $schema);

        $info = $this->data['constraints'][$schema][$table][$constraintName] ?? null;
        if (null === $info) {
            throw new Exception('Cannot find a constraint by that name in this table');
        }

        $constraint = new ConstraintObject($constraintName, $table, $schema);

        $constraintType = $info['constraint_type'] ?? null;
        if (null !== $constraintType) {
            $constraint->setType($constraintType);
        }

        $matchOption = $info['match_option'] ?? null;
        if (null !== $matchOption) {
            $constraint->setMatchOption($matchOption);
        }

        $updateRule = $info['update_rule'] ?? null;
        if (null !== $updateRule) {
            $constraint->setUpdateRule($updateRule);
        }

        $deleteRule = $info['delete_rule'] ?? null;
        if (null !== $deleteRule) {
            $constraint->setDeleteRule($deleteRule);
        }

        $columns = $info['columns'] ?? null;
        if (null !== $columns) {
            $constraint->setColumns($columns);
        }

        $referencedTableSchema = $info['referenced_table_schema'] ?? null;
        if (null !== $referencedTableSchema) {
            $constraint->setReferencedTableSchema($referencedTableSchema);
        }

        $referencedTableName = $info['referenced_table_name'] ?? null;
        if (null !== $referencedTableName) {
            $constraint->setReferencedTableName($referencedTableName);
        }

        $referencedColumns = $info['referenced_columns'] ?? null;
        if (null !== $referencedColumns) {
            $constraint->setReferencedColumns($referencedColumns);
        }

        $checkClause = $info['check_clause'] ?? null;
        if (null !== $checkClause) {
            $constraint->setCheckClause($checkClause);
        }

        return $constraint;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<ConstraintKeyObject>
     */
    #[Override]
    public function getConstraintKeys(string $constraint, string $table, ?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadConstraintReferences($table, $schema);

        // organize references first
        $references = [];
        foreach ($this->data['constraint_references'][$schema] ?? [] as $refKeyInfo) {
            if ($refKeyInfo['constraint_name'] !== $constraint) {
                continue;
            }

            $references[$refKeyInfo['constraint_name']] = $refKeyInfo;
        }

        $this->loadConstraintDataKeys($schema);

        $keys = [];
        foreach ($this->data['constraint_keys'][$schema] ?? [] as $constraintKeyInfo) {
            if (
                ! (

                        $constraintKeyInfo['table_name'] === $table
                        && $constraintKeyInfo['constraint_name'] === $constraint

                )
            ) {
                continue;
            }

            $key = new ConstraintKeyObject($constraintKeyInfo['column_name']);
            $key->setOrdinalPosition($constraintKeyInfo['ordinal_position']);

            $reference = $references[$constraint] ?? null;
            if (null !== $reference) {
                $key->setForeignKeyUpdateRule($reference['update_rule']);
                $key->setForeignKeyDeleteRule($reference['delete_rule']);
                $key->setReferencedTableName($reference['referenced_table_name']);
                $key->setReferencedColumnName($reference['referenced_column_name']);
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<ConstraintObject>
     *
     * @throws Exception If a constraint cannot be loaded.
     */
    #[Override]
    public function getConstraints(string $table, ?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadConstraintData($table, $schema);

        $constraints = [];
        foreach (array_keys($this->data['constraints'][$schema][$table] ?? []) as $constraintName) {
            $constraints[] = $this->getConstraint($constraintName, $table, $schema);
        }

        return $constraints;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<string>
     */
    #[Override]
    public function getSchemas(): array
    {
        $this->loadSchemaData();

        return $this->data['schemas'] ?? [];
    }

    /**
     * @throws Exception If the table does not exist or is of an unsupported type.
     */
    #[Override]
    public function getTable(string $tableName, ?string $schema = null): TableObject|ViewObject
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTableNameData($schema);

        $data = $this->data['table_names'][$schema][$tableName] ?? null;
        if (null === $data) {
            throw new Exception("Table \"{$tableName}\" does not exist");
        }

        switch ($data['table_type']) {
            case 'BASE TABLE':
                $table = new TableObject($tableName);
                break;
            case 'VIEW':
                $table = new ViewObject($tableName);
                $table->setViewDefinition($data['view_definition'] ?? null);
                $table->setCheckOption($data['check_option'] ?? null);
                $table->setIsUpdatable($data['is_updatable'] ?? null);
                break;
            default:
                throw new Exception(
                    "Table \"{$tableName}\" is of an unsupported type \"{$data['table_type']}\"",
                );
        }

        $table->setColumns($this->getColumns($tableName, $schema));
        $table->setConstraints($this->getConstraints($tableName, $schema));
        return $table;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<string>
     *
     * @mago-expect lint:no-boolean-flag-parameter
     */
    #[Override]
    public function getTableNames(?string $schema = null, bool $includeViews = false): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTableNameData($schema);

        $tableNamesData = $this->data['table_names'][$schema] ?? [];

        if ($includeViews) {
            return array_keys($tableNamesData);
        }

        $tableNames = [];
        foreach ($tableNamesData as $tableName => $data) {
            if ('BASE TABLE' !== $data['table_type']) {
                continue;
            }

            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<TableObject|ViewObject>
     *
     * @throws Exception If a table cannot be loaded.
     */
    #[Override]
    public function getTables(?string $schema = null, bool $includeViews = false): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $tables = [];
        foreach ($this->getTableNames($schema, $includeViews) as $tableName) {
            $tables[] = $this->getTable($tableName, $schema);
        }

        return $tables;
    }

    /**
     * @throws Exception If the trigger does not exist.
     */
    #[Override]
    public function getTrigger(string $triggerName, ?string $schema = null): TriggerObject
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTriggerData($schema);

        $info = $this->data['triggers'][$schema][$triggerName] ?? null;
        if (null === $info) {
            throw new Exception("Trigger \"{$triggerName}\" does not exist");
        }

        $trigger = new TriggerObject();

        $trigger->setName($triggerName);
        $trigger->setEventManipulation($info['event_manipulation']);
        $trigger->setEventObjectCatalog($info['event_object_catalog']);
        $trigger->setEventObjectSchema($info['event_object_schema']);
        $trigger->setEventObjectTable($info['event_object_table']);
        $trigger->setActionOrder($info['action_order']);
        $trigger->setActionCondition($info['action_condition']);
        $trigger->setActionStatement($info['action_statement']);
        $trigger->setActionOrientation($info['action_orientation']);
        $trigger->setActionTiming($info['action_timing']);
        $trigger->setActionReferenceOldTable($info['action_reference_old_table']);
        $trigger->setActionReferenceNewTable($info['action_reference_new_table']);
        $trigger->setActionReferenceOldRow($info['action_reference_old_row']);
        $trigger->setActionReferenceNewRow($info['action_reference_new_row']);
        $trigger->setCreated($info['created']);

        return $trigger;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<string>
     */
    #[Override]
    public function getTriggerNames(?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTriggerData($schema);

        return array_keys($this->data['triggers'][$schema] ?? []);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<TriggerObject>
     *
     * @throws Exception If a trigger cannot be loaded.
     */
    #[Override]
    public function getTriggers(?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $triggers = [];
        foreach ($this->getTriggerNames($schema) as $triggerName) {
            $triggers[] = $this->getTrigger($triggerName, $schema);
        }

        return $triggers;
    }

    /**
     * @throws Exception If the view does not exist.
     */
    #[Override]
    public function getView(string $viewName, ?string $schema = null): ViewObject|TableObject
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTableNameData($schema);

        $viewInfo = $this->data['table_names'][$schema][$viewName] ?? null;
        if (null !== $viewInfo && 'VIEW' === $viewInfo['table_type']) {
            return $this->getTable($viewName, $schema);
        }

        throw new Exception("View \"{$viewName}\" does not exist");
    }

    /**
     * {@inheritdoc}
     *
     * @return list<string>
     */
    #[Override]
    public function getViewNames(?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $this->loadTableNameData($schema);

        $viewNames = [];
        foreach ($this->data['table_names'][$schema] ?? [] as $tableName => $data) {
            if ('VIEW' !== $data['table_type']) {
                continue;
            }

            $viewNames[] = $tableName;
        }

        return $viewNames;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<TableObject|ViewObject>
     *
     * @throws Exception If a view cannot be loaded.
     */
    #[Override]
    public function getViews(?string $schema = null): array
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }

        $views = [];
        foreach ($this->getViewNames($schema) as $tableName) {
            $views[] = $this->getTable($tableName, $schema);
        }

        return $views;
    }

    /**
     * Load schema data
     */
    abstract protected function loadSchemaData(): void;

    /**
     * Load column data
     */
    protected function loadColumnData(string $table, string $schema): void
    {
        if (null !== ($this->data['columns'][$schema][$table] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('columns', $schema, $table);
    }

    /**
     * Load constraint data
     *
     * $table is unused here but forms part of the signature that concrete
     * sources override to load per-table constraint data.
     *
     * @mago-expect analysis:unused-parameter
     */
    protected function loadConstraintData(string $table, string $schema): void
    {
        if (null !== ($this->data['constraints'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraints', $schema);
    }

    /**
     * Load constraint data keys
     */
    protected function loadConstraintDataKeys(string $schema): void
    {
        if (null !== ($this->data['constraint_keys'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraint_keys', $schema);
    }

    /**
     * Load constraint references
     *
     * $table is unused here but forms part of the signature that concrete
     * sources override to load per-table constraint references.
     *
     * @mago-expect analysis:unused-parameter
     */
    protected function loadConstraintReferences(string $table, string $schema): void
    {
        if (null !== ($this->data['constraint_references'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraint_references', $schema);
    }

    /**
     * Load table name data
     */
    protected function loadTableNameData(string $schema): void
    {
        if (null !== ($this->data['table_names'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('table_names', $schema);
    }

    /**
     * Load trigger data
     */
    protected function loadTriggerData(string $schema): void
    {
        if (null !== ($this->data['triggers'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('triggers', $schema);
    }

    /**
     * Prepare data hierarchy
     *
     * The by-reference walk builds arbitrary depths of the hierarchy, which
     * cannot be expressed against the MetadataData shape. The reference is
     * re-typed as a plain array so the analyzer does not re-derive the shape
     * on every iteration, which does not terminate in useful time on Mago
     * 1.47.5 and later.
     *
     * @mago-expect analysis:mixed-assignment
     * @mago-expect lint:no-isset
     */
    protected function prepareDataHierarchy(string $type, string ...$keys): void
    {
        /** @var array<array-key, mixed> $data */
        $data = &$this->data;
        foreach ([$type, ...$keys] as $key) {
            if (! isset($data[$key])) {
                $data[$key] = [];
            }

            $data = &$data[$key];
        }
    }
}
