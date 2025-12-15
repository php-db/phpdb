<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;

use function array_key_exists;
use function implode;
use function is_scalar;

abstract class AbstractInsert extends AbstractPreparableSql
{
    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    protected ?TableIdentifier $table = null;

    protected ?Values $values = null;

    protected ?Select $select = null;

    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->into($table);
        }
    }

    private function getValues(): Values
    {
        return $this->values ??= new Values();
    }

    public function into(TableIdentifier|string|array $table): static
    {
        $this->table = TableIdentifier::from($table);
        return $this;
    }

    public function columns(array $columns): static
    {
        $this->getValues()->columns($columns);
        return $this;
    }

    public function values(array|Select $values, string $flag = self::VALUES_SET): static
    {
        if ($values instanceof Select) {
            if ($flag === self::VALUES_MERGE) {
                throw new Exception\InvalidArgumentException(
                    'A PhpDb\Sql\Select instance cannot be provided with the merge flag'
                );
            }

            $this->select = $values;
            return $this;
        }

        if ($this->select !== null && $flag === self::VALUES_MERGE) {
            throw new Exception\InvalidArgumentException(
                'An array of values cannot be provided with the merge flag when a PhpDb\Sql\Select'
                . ' instance already exists as the value source'
            );
        }

        $this->getValues()->set($values, $flag);
        return $this;
    }

    public function select(Select $select): static
    {
        return $this->values($select);
    }

    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            'table'  => $this->table,
            'values' => $this->values,
            'select' => $this->select,
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    abstract protected function getInsertKeyword(): string;

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $q        = $platform->getQuoteIdentifierSymbol();
        $tableSql = $this->table?->prepareSqlString($q) ?? '';

        if ($this->select !== null) {
            return $this->buildInsertSelectSql($tableSql, $q, $platform, $driver, $parameterContainer);
        }

        return $this->buildInsertValuesSql($tableSql, $q, $platform, $driver, $parameterContainer);
    }

    protected function buildInsertValuesSql(
        string $tableSql,
        string $q,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $valuesObj = $this->getValues();

        if ($valuesObj->count() === 0) {
            throw new Exception\InvalidArgumentException('values or select should be present');
        }

        $columns     = [];
        $valueParts  = [];
        $i           = 0;
        $isPdoDriver = $driver instanceof PdoDriverInterface;

        foreach ($valuesObj as $column => $value) {
            $columns[] = $q . $column . $q;

            if (is_scalar($value) && $parameterContainer !== null && $driver !== null) {
                $paramName    = $isPdoDriver ? 'c_' . $i++ : $column;
                $valueParts[] = $driver->formatParameterName($paramName);
                $parameterContainer->offsetSet($paramName, $value);
            } else {
                $valueParts[] = $this->resolveColumnValue(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer
                );
            }
        }

        return $this->getInsertKeyword() . ' INTO ' . $tableSql
             . ' (' . implode(', ', $columns) . ')'
             . ' VALUES (' . implode(', ', $valueParts) . ')';
    }

    protected function buildInsertSelectSql(
        string $tableSql,
        string $q,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $selectSql = $this->processSubSelect($this->select, $platform, $driver, $parameterContainer);

        $columnsPart = '';
        $valuesObj   = $this->values;
        if ($valuesObj !== null && $valuesObj->count() > 0) {
            $columns = [];
            foreach ($valuesObj->getColumns() as $col) {
                $columns[] = $q . $col . $q;
            }
            $columnsPart = ' (' . implode(', ', $columns) . ')';
        }

        return $this->getInsertKeyword() . ' INTO ' . $tableSql . $columnsPart . ' ' . $selectSql;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->getValues()->merge($name, $value);
    }

    public function __unset(string $name): void
    {
        if (! $this->getValues()->has($name)) {
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        $this->getValues()->remove($name);
    }

    public function __isset(string $name): bool
    {
        return $this->values !== null && $this->values->has($name);
    }

    public function __get(string $name): mixed
    {
        if ($this->values === null || ! $this->values->has($name)) {
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        return $this->values->get($name);
    }

    public function __clone()
    {
        if ($this->values !== null) {
            $this->values = clone $this->values;
        }
        if ($this->select !== null) {
            $this->select = clone $this->select;
        }
    }
}
