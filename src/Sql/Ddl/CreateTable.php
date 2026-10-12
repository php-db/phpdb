<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;
use function is_bool;
use function is_int;
use function strtoupper;

/**
 * @api
 *
 * @psalm-import-type Specification from AbstractSql
 * @psalm-type RawState = array{
 *     columns: list<Column\ColumnInterface>,
 *     constraints: list<Constraint\ConstraintInterface>,
 *     table: string|TableIdentifier,
 *     tableOptions: array<string, Literal|bool|int|string>,
 * }
 */
class CreateTable extends AbstractSql
{
    final public const string COLUMNS = 'columns';

    final public const string CONSTRAINTS = 'constraints';

    final public const string TABLE = 'table';

    final public const string TABLE_OPTIONS = 'tableOptions';

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
     * @return RawState|value-of<RawState>
     */
    public function getRawState(?string $key = null): array|string|TableIdentifier
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
     * @throws RuntimeException
     */
    protected function processColumns(PlatformInterface $adapterPlatform): ?array
    {
        if (! $this->columns) {
            return null;
        }

        return [$this->processExpressionList($this->columns, $adapterPlatform)];
    }

    /**
     * @return array{}|null
     */
    protected function processCombinedby(): ?array
    {
        return [] !== $this->constraints && [] !== $this->columns ? [] : null;
    }

    /**
     * @return string[][]|null
     * @throws RuntimeException
     */
    protected function processConstraints(PlatformInterface $adapterPlatform): ?array
    {
        if (! $this->constraints) {
            return null;
        }

        return [$this->processExpressionList($this->constraints, $adapterPlatform)];
    }

    /**
     * @return string[]
     */
    protected function processStatementEnd(): array
    {
        return ["\n)"];
    }

    /**
     * @return string[]
     * @throws RuntimeException
     */
    protected function processTable(PlatformInterface $adapterPlatform): array
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
    protected function processTableOptions(PlatformInterface $adapterPlatform): ?array
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
