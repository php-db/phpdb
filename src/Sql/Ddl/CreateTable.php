<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;

class CreateTable extends AbstractSql
{
    final public const COLUMNS = 'columns';

    final public const CONSTRAINTS = 'constraints';

    final public const TABLE = 'table';

    protected array $columns = [];

    protected array $constraints = [];

    protected bool $isTemporary = false;

    /**
     * {@inheritDoc}
     */
    protected array $specifications = [
        self::TABLE       => 'CREATE %1$sTABLE %2$s (',
        self::COLUMNS     => [
            "\n    %1\$s" => [
                [1 => '%1$s', 'combinedby' => ",\n    "],
            ],
        ],
        'combinedBy'      => ',',
        self::CONSTRAINTS => [
            "\n    %1\$s" => [
                [1 => '%1$s', 'combinedby' => ",\n    "],
            ],
        ],
        'statementEnd'    => '%1$s',
    ];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '', bool $isTemporary = false)
    {
        $this->table = $table;
        $this->setTemporary($isTemporary);
    }

    public function setTemporary(string|int|bool $temporary): static
    {
        $this->isTemporary = (bool) $temporary;
        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    public function setTable(string $name): static
    {
        $this->table = $name;
        return $this;
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

    /**
     * @return ((Column\ColumnInterface|string)[]|Column\ColumnInterface|string)[]|string
     * @psalm-return array<Column\ColumnInterface|array<Column\ColumnInterface|string>|string>|string
     */
    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::COLUMNS     => $this->columns,
            self::CONSTRAINTS => $this->constraints,
            self::TABLE       => $this->table,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * @return string[]
     */
    protected function processTable(?PlatformInterface $adapterPlatform = null): array
    {
        return [
            $this->isTemporary ? 'TEMPORARY ' : '',
            $this->resolveTable($this->table, $adapterPlatform),
        ];
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

    protected function processCombinedby(?PlatformInterface $adapterPlatform = null): string|null
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
}
