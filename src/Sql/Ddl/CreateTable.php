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

class CreateTable extends AbstractPreparableSql implements SqlInterface
{
    final public const COLUMNS = 'columns';

    final public const CONSTRAINTS = 'constraints';

    final public const TABLE = 'table';

    protected array $columns = [];

    protected array $constraints = [];

    protected bool $isTemporary = false;

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

    /** @inheritDoc */
    #[Override]
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $builder = new PreparableSqlBuilder($platform, $driver, $parameterContainer);
        $q       = $builder->q;

        // CREATE [TEMPORARY] TABLE name
        $sql = 'CREATE ' . ($this->isTemporary ? 'TEMPORARY ' : '') . 'TABLE ';

        // Table name
        if ($this->table instanceof TableIdentifier) {
            $schema = $this->table->getSchema();
            $sql   .= $schema !== null ? "{$q}{$schema}{$q}.{$q}{$this->table->getTable()}{$q}"
                : "{$q}{$this->table->getTable()}{$q}";
        } else {
            $sql .= "{$q}{$this->table}{$q}";
        }

        $sql .= ' ( ';

        // Build column SQL strings
        $columnSqls = [];
        foreach ($this->columns as $column) {
            $columnSqls[] = $column->prepareSqlString($builder);
        }

        // Build constraint SQL strings
        $constraintSqls = [];
        foreach ($this->constraints as $constraint) {
            $constraintSqls[] = $constraint->prepareSqlString($builder);
        }

        $hasColumns     = $columnSqls !== [];
        $hasConstraints = $constraintSqls !== [];

        if ($hasColumns && $hasConstraints) {
            // Both: columns joined with ",\n    ", then " , " separator, then constraints
            $colPart        = implode(",\n    ", $columnSqls);
            $constraintPart = implode(",\n    ", $constraintSqls);
            $sql           .= "\n    {$colPart} , \n    {$constraintPart} ";
        } elseif ($hasColumns) {
            // Only columns
            $colPart = implode(",\n    ", $columnSqls);
            $sql    .= "\n    {$colPart} ";
        } elseif ($hasConstraints) {
            // Only constraints
            $constraintPart = implode(",\n    ", $constraintSqls);
            $sql           .= "\n    {$constraintPart} ";
        }

        return "{$sql}\n)";
    }
}
