<?php

declare(strict_types=1);

namespace Laminas\Db\Metadata;

use Laminas\Db\Metadata\Object\ColumnObject;
use Laminas\Db\Metadata\Object\ConstraintKeyObject;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Object\TableObject;
use Laminas\Db\Metadata\Object\TriggerObject;
use Laminas\Db\Metadata\Object\ViewObject;
use Symfony\Component\Console\Helper\Table;

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

    public function getTable(string $tableName, ?string $schema = null): TableObject;

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
