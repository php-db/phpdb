<?php

declare(strict_types=1);

namespace PhpDb\Metadata;

use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintKeyObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\TriggerObject;
use PhpDb\Metadata\Object\ViewObject;

interface MetadataInterface
{
    /**
     * @return string[]
     */
    public function getSchemas(): array;

    /**
     * @return string[]
     */
    public function getTableNames(?string $schema = null, bool $includeViews = false): array;

    /**
     * @return TableObject[]
     */
    public function getTables(?string $schema = null, bool $includeViews = false): array;

    public function getTable(string $tableName, ?string $schema = null): TableObject|ViewObject;

    /**
     * @return string[]
     */
    public function getViewNames(?string $schema = null): array;

    /**
     * @return ViewObject[]
     */
    public function getViews(?string $schema = null): array;

    public function getView(string $viewName, ?string $schema = null): ViewObject|TableObject;

    public function getColumnNames(string $table, ?string $schema = null): array;

    /**
     * @return ColumnObject[]
     */
    public function getColumns(string $table, ?string $schema = null): array;

    public function getColumn(string $columnName, string $table, ?string $schema = null): ColumnObject;

    /**
     * @return ConstraintObject[]
     */
    public function getConstraints(string $table, ?string $schema = null): array;

    public function getConstraint(
        string $constraintName,
        string $table,
        ?string $schema = null
    ): ConstraintObject;

    /**
     * @return ConstraintKeyObject[]
     */
    public function getConstraintKeys(string $constraint, string $table, ?string $schema = null): array;

    /**
     * @return string[]
     */
    public function getTriggerNames(?string $schema = null): array;

    /**
     * @return TriggerObject[]
     */
    public function getTriggers(?string $schema = null): array;

    public function getTrigger(string $triggerName, ?string $schema = null): TriggerObject;
}
