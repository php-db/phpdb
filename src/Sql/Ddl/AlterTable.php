<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;

class AlterTable extends AbstractSql
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

    /**
     * Specifications for Sql String generation
     */
    protected array $specifications = [
        self::TABLE            => "ALTER TABLE %1\$s\n",
        self::ADD_COLUMNS      => [
            "%1\$s" => [
                [1 => "ADD COLUMN %1\$s,\n", 'combinedby' => ''],
            ],
        ],
        self::CHANGE_COLUMNS   => [
            "%1\$s" => [
                [2 => "CHANGE COLUMN %1\$s %2\$s,\n", 'combinedby' => ''],
            ],
        ],
        self::DROP_COLUMNS     => [
            "%1\$s" => [
                [1 => "DROP COLUMN %1\$s,\n", 'combinedby' => ''],
            ],
        ],
        self::ADD_CONSTRAINTS  => [
            "%1\$s" => [
                [1 => "ADD %1\$s,\n", 'combinedby' => ''],
            ],
        ],
        self::DROP_CONSTRAINTS => [
            "%1\$s" => [
                [1 => "DROP CONSTRAINT %1\$s,\n", 'combinedby' => ''],
            ],
        ],
        self::DROP_INDEXES     => [
            '%1$s' => [
                [1 => "DROP INDEX %1\$s,\n", 'combinedby' => ''],
            ],
        ],
    ];

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

    /** @return string[] */
    protected function processTable(?PlatformInterface $adapterPlatform = null): array
    {
        return [$this->resolveTable($this->table, $adapterPlatform)];
    }

    /**
     * @return string[][]
     * @psalm-return list{list{0?: string,...}}
     */
    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->addColumns as $column) {
            $sqls[] = $this->processExpression($column, $adapterPlatform);
        }

        return [$sqls];
    }

    /**
     * @return string[][][]
     * @psalm-return list{list{0?: list{string, string},...}}
     */
    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->changeColumns as $name => $column) {
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $this->processExpression($column, $adapterPlatform),
            ];
        }

        return [$sqls];
    }

    /**
     * @return string[][]
     * @psalm-return list{list{0?: string,...}}
     */
    protected function processDropColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->dropColumns as $column) {
            $sqls[] = $adapterPlatform->quoteIdentifier($column);
        }

        return [$sqls];
    }

    /**
     * @return string[][]
     * @psalm-return list{list{0?: string,...}}
     */
    protected function processAddConstraints(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->addConstraints as $constraint) {
            $sqls[] = $this->processExpression($constraint, $adapterPlatform);
        }

        return [$sqls];
    }

    /**
     * @return string[][]
     * @psalm-return list{list{0?: string,...}}
     */
    protected function processDropConstraints(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->dropConstraints as $constraint) {
            $sqls[] = $adapterPlatform->quoteIdentifier($constraint);
        }

        return [$sqls];
    }

    /**
     * @return string[][]
     * @psalm-return list{list{0?: string,...}}
     */
    protected function processDropIndexes(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->dropIndexes as $index) {
            $sqls[] = $adapterPlatform->quoteIdentifier($index);
        }

        return [$sqls];
    }
}
