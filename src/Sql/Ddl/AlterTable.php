<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function array_map;
use function is_bool;
use function is_int;
use function strtoupper;

/**
 * @api
 *
 * @psalm-import-type Specification from AbstractSql
 * @psalm-type RawState = array{
 *     table: string|TableIdentifier,
 *     addColumns: list<Column\ColumnInterface>,
 *     dropColumns: list<string>,
 *     changeColumns: array<string, Column\ColumnInterface>,
 *     addConstraints: list<Constraint\ConstraintInterface>,
 *     dropConstraints: list<string>,
 *     dropIndexes: list<string>,
 *     tableOptions: array<string, Literal|bool|int|string>,
 * }
 */
class AlterTable extends AbstractSql
{
    final public const string ADD_COLUMNS = 'addColumns';

    final public const string ADD_CONSTRAINTS = 'addConstraints';

    final public const string CHANGE_COLUMNS = 'changeColumns';

    final public const string DROP_COLUMNS = 'dropColumns';

    final public const string DROP_CONSTRAINTS = 'dropConstraints';

    final public const string DROP_INDEXES = 'dropIndexes';

    final public const string TABLE = 'table';

    final public const string TABLE_OPTIONS = 'tableOptions';

    /** @var list<Column\ColumnInterface> */
    protected array $addColumns = [];

    /** @var list<Constraint\ConstraintInterface> */
    protected array $addConstraints = [];

    /** @var array<string, Column\ColumnInterface> */
    protected array $changeColumns = [];

    /** @var list<string> */
    protected array $dropColumns = [];

    /** @var list<string> */
    protected array $dropConstraints = [];

    /** @var list<string> */
    protected array $dropIndexes = [];

    /** @var array<string, Literal|bool|int|string> */
    protected array $options = [];

    /**
     * Specifications for Sql String generation
     *
     * @var array<string, Specification>
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
        self::TABLE_OPTIONS    => [
            "%1\$s" => [
                [1 => "%1\$s,\n", 'combinedby' => ''],
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

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->addColumns[] = $column;

        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->addConstraints[] = $constraint;

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

    /**
     * @return static Provides a fluent interface
     */
    public function dropIndex(string $name): static
    {
        $this->dropIndexes[] = $name;

        return $this;
    }

    /** @return array<string, Literal|bool|int|string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return RawState|value-of<RawState>
     */
    public function getRawState(?string $key = null): array|string|TableIdentifier
    {
        $rawState = [
            self::TABLE            => $this->table,
            self::ADD_COLUMNS      => $this->addColumns,
            self::DROP_COLUMNS     => $this->dropColumns,
            self::CHANGE_COLUMNS   => $this->changeColumns,
            self::ADD_CONSTRAINTS  => $this->addConstraints,
            self::DROP_CONSTRAINTS => $this->dropConstraints,
            self::DROP_INDEXES     => $this->dropIndexes,
            self::TABLE_OPTIONS    => $this->options,
        ];

        return null !== $key && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    public function setOption(string $name, Literal|bool|int|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    /** @param array<string, Literal|bool|int|string> $options */
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function setTable(string|TableIdentifier $name): static
    {
        $this->table = $name;

        return $this;
    }

    /**
     * @return array{0: list<string>}
     * @throws \PhpDb\Sql\Exception\RuntimeException
     */
    protected function processAddColumns(PlatformInterface $adapterPlatform): array
    {
        return [$this->processExpressionList($this->addColumns, $adapterPlatform)];
    }

    /**
     * @return array{0: list<string>}
     * @throws \PhpDb\Sql\Exception\RuntimeException
     */
    protected function processAddConstraints(PlatformInterface $adapterPlatform): array
    {
        return [$this->processExpressionList($this->addConstraints, $adapterPlatform)];
    }

    /**
     * @return array{0: list<array{0: string, 1: string}>}
     * @throws \PhpDb\Sql\Exception\RuntimeException
     */
    protected function processChangeColumns(PlatformInterface $adapterPlatform): array
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
     * @return array{0: list<string>}
     */
    protected function processDropColumns(PlatformInterface $adapterPlatform): array
    {
        return [array_map($adapterPlatform->quoteIdentifier(...), $this->dropColumns)];
    }

    /**
     * @return array{0: list<string>}
     */
    protected function processDropConstraints(PlatformInterface $adapterPlatform): array
    {
        return [array_map($adapterPlatform->quoteIdentifier(...), $this->dropConstraints)];
    }

    /**
     * @return array{0: list<string>}
     */
    protected function processDropIndexes(PlatformInterface $adapterPlatform): array
    {
        return [array_map($adapterPlatform->quoteIdentifier(...), $this->dropIndexes)];
    }

    /**
     * @return string[]
     * @throws \PhpDb\Sql\Exception\RuntimeException
     */
    protected function processTable(PlatformInterface $adapterPlatform): array
    {
        return [$this->resolveTable($this->table, $adapterPlatform)];
    }

    /**
     * @return array{0: list<string>}|null
     */
    protected function processTableOptions(PlatformInterface $adapterPlatform): ?array
    {
        if (! $this->options) {
            return null;
        }

        $sqls = [];
        foreach ($this->options as $key => $value) {
            $key   = strtoupper($key);
            $value = match (true) {
                $value instanceof Literal => $value->getLiteral(),
                is_bool($value) => $value ? '1' : '0',
                is_int($value)  => (string) $value,
                default         => $adapterPlatform->quoteTrustedValue($value),
            };
            $sqls[] = "{$key} = {$value}";
        }

        return [$sqls];
    }
}
