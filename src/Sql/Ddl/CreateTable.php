<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;
use function is_bool;
use function is_int;
use function strtoupper;

/**
 * @psalm-import-type Specification from AbstractSql
 */
class CreateTable extends AbstractSql
{
    final public const COLUMNS = 'columns';

    final public const CONSTRAINTS = 'constraints';

    final public const TABLE = 'table';

    final public const TABLE_OPTIONS = 'tableOptions';

    /** @var list<Column\ColumnInterface> */
    protected array $columns = [];

    /** @var list<Constraint\ConstraintInterface> */
    protected array $constraints = [];

    protected bool $ifNotExists = false;

    protected bool $isTemporary = false;

    /** @var array<string, Literal|bool|int|string> */
    protected array $options = [];

    /** @var array<string, Specification> */
    protected array $specifications = [
        self::TABLE         => 'CREATE %1$sTABLE %2$s%3$s (',
        self::COLUMNS       => [
            "\n    %1\$s" => [
                [1 => '%1$s', 'combinedby' => ",\n    "],
            ],
        ],
        'combinedBy'        => ',',
        self::CONSTRAINTS   => [
            "\n    %1\$s" => [
                [1 => '%1$s', 'combinedby' => ",\n    "],
            ],
        ],
        'statementEnd'      => '%1$s',
        self::TABLE_OPTIONS => '%1$s',
    ];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '', bool $isTemporary = false)
    {
        $this->table = $table;
        $this->setTemporary($isTemporary);
    }

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;
        return $this;
    }

    public function getIfNotExists(): bool
    {
        return $this->ifNotExists;
    }

    /** @return array<string, Literal|bool|int|string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return ((Column\ColumnInterface|string)[]|Column\ColumnInterface|string)[]|string
     * @psalm-return array<Column\ColumnInterface|array<Column\ColumnInterface|string>|string>|string
     */
    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::COLUMNS       => $this->columns,
            self::CONSTRAINTS   => $this->constraints,
            self::TABLE         => $this->table,
            self::TABLE_OPTIONS => $this->options,
        ];

        return null !== $key && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    public function ifNotExists(bool $ifNotExists = true): static
    {
        $this->ifNotExists = $ifNotExists;
        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
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

    public function setTable(string $name): static
    {
        $this->table = $name;
        return $this;
    }

    public function setTemporary(string|int|bool $temporary): static
    {
        $this->isTemporary = (bool) $temporary;
        return $this;
    }

    /**
     * @return string[][]|null
     */
    protected function processColumns(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->columns) {
            return null;
        }

        $sqls = [];

        foreach ($this->columns as $column) {
            $sqls[] = $this->processExpression($column, $adapterPlatform);
        }

        return [$sqls];
    }

    protected function processCombinedby(?PlatformInterface $adapterPlatform = null): ?string
    {
        if ($this->constraints && $this->columns) {
            return $this->specifications['combinedBy'];
        }

        return null;
    }

    /**
     * @return string[][]|null
     */
    protected function processConstraints(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->constraints) {
            return null;
        }

        $sqls = [];

        foreach ($this->constraints as $constraint) {
            $sqls[] = $this->processExpression($constraint, $adapterPlatform);
        }

        return [$sqls];
    }

    /**
     * @return string[]
     */
    protected function processStatementEnd(?PlatformInterface $adapterPlatform = null): array
    {
        return ["\n)"];
    }

    /**
     * @return string[]
     */
    protected function processTable(?PlatformInterface $adapterPlatform = null): array
    {
        return [
            $this->isTemporary ? 'TEMPORARY ' : '',
            $this->ifNotExists ? 'IF NOT EXISTS ' : '',
            $this->resolveTable($this->table, $adapterPlatform),
        ];
    }

    /**
     * @return string[]|null
     */
    protected function processTableOptions(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->options) {
            return null;
        }

        $parts = [];
        foreach ($this->options as $key => $value) {
            $key   = strtoupper($key);
            $value = match (true) {
                $value instanceof Literal => $value->getLiteral(),
                is_bool($value) => $value ? '1' : '0',
                is_int($value)  => (string) $value,
                default         => $adapterPlatform->quoteTrustedValue($value),
            };
            $parts[] = "{$key} = {$value}";
        }

        return [implode(' ', $parts)];
    }
}
