<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractPreparableSql;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;

class AlterTable extends AbstractPreparableSql implements SqlInterface
{
    final public const ADD_COLUMNS = 'addColumns';

    final public const ADD_CONSTRAINTS = 'addConstraints';

    final public const CHANGE_COLUMNS = 'changeColumns';

    final public const DROP_COLUMNS = 'dropColumns';

    final public const DROP_CONSTRAINTS = 'dropConstraints';

    final public const DROP_INDEXES = 'dropIndexes';

    final public const TABLE = 'table';

    protected array $addColumns = [];

    protected array $addConstraints = [];

    protected array $changeColumns = [];

    protected array $dropColumns = [];

    protected array $dropConstraints = [];

    protected array $dropIndexes = [];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        if ($table) {
            $this->setTable($table);
        }
    }

    public function setTable(string|TableIdentifier $name): static
    {
        $this->table = $name;

        return $this;
    }

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->addColumns[] = $column;

        return $this;
    }

    public function changeColumn(string $name, Column\ColumnInterface $column): static
    {
        $this->changeColumns[$name] = $column;

        return $this;
    }

    public function dropColumn(string $name): static
    {
        $this->dropColumns[] = $name;

        return $this;
    }

    public function dropConstraint(string $name): static
    {
        $this->dropConstraints[] = $name;

        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->addConstraints[] = $constraint;

        return $this;
    }

    /**
     * @return static Provides a fluent interface
     */
    public function dropIndex(string $name): static
    {
        $this->dropIndexes[] = $name;

        return $this;
    }

    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::TABLE            => $this->table,
            self::ADD_COLUMNS      => $this->addColumns,
            self::DROP_COLUMNS     => $this->dropColumns,
            self::CHANGE_COLUMNS   => $this->changeColumns,
            self::ADD_CONSTRAINTS  => $this->addConstraints,
            self::DROP_CONSTRAINTS => $this->dropConstraints,
            self::DROP_INDEXES     => $this->dropIndexes,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /** @inheritDoc */
    #[Override]
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $builder = new PreparableSqlBuilder($platform, $driver, $parameterContainer);
        $q       = $builder->q;

        // ALTER TABLE name
        $sql = 'ALTER TABLE ';
        if ($this->table instanceof TableIdentifier) {
            $schema = $this->table->getSchema();
            $sql   .= $schema !== null ? "{$q}{$schema}{$q}.{$q}{$this->table->getTable()}{$q}"
                : "{$q}{$this->table->getTable()}{$q}";
        } else {
            $sql .= "{$q}{$this->table}{$q}";
        }

        $statements = [];

        // ADD COLUMN statements
        foreach ($this->addColumns as $column) {
            $statements[] = 'ADD COLUMN ' . $column->prepareSqlString($builder);
        }

        // CHANGE COLUMN statements
        foreach ($this->changeColumns as $name => $column) {
            $statements[] = "CHANGE COLUMN {$q}{$name}{$q} " . $column->prepareSqlString($builder);
        }

        // DROP COLUMN statements
        foreach ($this->dropColumns as $column) {
            $statements[] = "DROP COLUMN {$q}{$column}{$q}";
        }

        // ADD constraint statements
        foreach ($this->addConstraints as $constraint) {
            $statements[] = 'ADD ' . $constraint->prepareSqlString($builder);
        }

        // DROP CONSTRAINT statements
        foreach ($this->dropConstraints as $constraint) {
            $statements[] = "DROP CONSTRAINT {$q}{$constraint}{$q}";
        }

        // DROP INDEX statements
        foreach ($this->dropIndexes as $index) {
            $statements[] = "DROP INDEX {$q}{$index}{$q}";
        }

        if ($statements !== []) {
            $stmtSql = implode(",\n ", $statements);
            $sql    .= "\n {$stmtSql}";
        }

        return $sql;
    }
}
