<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;

use function array_combine;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function is_scalar;
use function range;

/**
 * @api
 *
 * @psalm-import-type Specification from AbstractSql
 * @psalm-import-type ColumnValue from AbstractSql
 */
class Insert extends AbstractPreparableSql
{
    /**
     * Constants
     *
     * @const
     */
    public const string SPECIFICATION_INSERT = 'insert';

    final public const string SPECIFICATION_SELECT = 'select';

    final public const string VALUES_MERGE = 'merge';

    final public const string VALUES_SET = 'set';

    /** @var array<string, Specification> */
    protected array $specifications = [
        self::SPECIFICATION_INSERT => 'INSERT INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'INSERT INTO %1$s %2$s %3$s',
    ];

    /** @var TableIdentifier|string|array<string, string|TableIdentifier> */
    protected TableIdentifier|string|array $table = '';

    /** @var array<array-key, ColumnValue> */
    protected array $columns = [];

    /** @var Select|null */
    protected array|Select|null $select = null;

    /**
     * Constructor
     *
     * @param string|TableIdentifier|array<string, string|TableIdentifier>|null $table An aliased table
     *     array is kept for TableGateway and rendered as the bare table
     */
    public function __construct(string|TableIdentifier|array|null $table = null)
    {
        if ($table) {
            $this->into($table);
        }
    }

    /**
     * Specify columns
     *
     * @param list<string> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = array_flip($columns);
        return $this;
    }

    /**
     * Get raw state
     *
     * @return ($key is 'table' ? TableIdentifier|string|array<string, string|TableIdentifier> : (
     *     TableIdentifier|string|array<string, string|TableIdentifier>|list<array-key|ColumnValue>|array{
     *         table: TableIdentifier|string|array<string, string|TableIdentifier>,
     *         columns: list<array-key>,
     *         values: list<ColumnValue>,
     *     }
     * ))
     */
    public function getRawState(?string $key = null): TableIdentifier|string|array
    {
        $rawState = [
            'table'   => $this->table,
            'columns' => array_keys($this->columns),
            'values'  => array_values($this->columns),
        ];
        return null !== $key && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /**
     * Create INTO clause
     *
     * @param TableIdentifier|string|array<string, string|TableIdentifier> $table
     */
    public function into(TableIdentifier|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Create INTO SELECT clause
     */
    public function select(Select $select): static
    {
        return $this->values($select);
    }

    /**
     * Specify values to insert
     *
     * @param array<array-key, ColumnValue>|Select $values
     * @param string                        $flag one of VALUES_MERGE or VALUES_SET; defaults to VALUES_SET
     * @throws Exception\InvalidArgumentException
     */
    public function values(array|Select $values, string $flag = self::VALUES_SET): static
    {
        if ($values instanceof Select) {
            if (self::VALUES_MERGE === $flag) {
                throw Exception\InvalidArgumentException::forSelectWithMergeFlag();
            }

            $this->select = $values;
            return $this;
        }

        if (null !== $this->select && self::VALUES_MERGE === $flag) {
            throw Exception\InvalidArgumentException::forValuesWithMergeFlag();
        }

        if (self::VALUES_SET === $flag) {
            $this->columns = $this->isAssocativeArray($values)
                ? $values
                : array_combine(array_keys($this->columns), array_values($values));

            return $this;
        }

        foreach ($values as $column => $value) {
            $this->columns[$column] = $value;
        }

        return $this;
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processInsert(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
        if ($this->select) {
            return null;
        }

        if (! $this->columns) {
            throw Exception\InvalidArgumentException::forMissingValuesOrSelect();
        }

        $columns     = [];
        $values      = [];
        $i           = 0;
        $isPdoDriver = $driver instanceof PdoDriverInterface;

        foreach ($this->columns as $column => $value) {
            $columns[] = $platform->quoteIdentifier((string) $column);
            if (is_scalar($value) && $parameterContainer && $driver instanceof DriverInterface) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                if ($isPdoDriver) {
                    $column = 'c_' . $i++;
                }

                $values[] = $driver->formatParameterName((string) $column);
                $parameterContainer->offsetSet($column, $value);
                continue;
            }

            $values[] = $this->resolveColumnValue(
                $value,
                $platform,
                $driver,
                $parameterContainer,
            );
        }

        return $this->formatSpecification(
            static::SPECIFICATION_INSERT,
            $this->resolveTable($this->unaliasTable($this->table), $platform, $driver, $parameterContainer),
            implode(', ', $columns),
            implode(', ', $values),
        );
    }

    /**
     * @throws Exception\RuntimeException
     */
    protected function processSelect(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
        if (! $this->select) {
            return null;
        }

        $selectSql = $this->processSubSelect($this->select, $platform, $driver, $parameterContainer);

        $columns = implode(', ', array_map(
            static fn(int|string $column): string => $platform->quoteIdentifier((string) $column),
            array_keys($this->columns),
        ));

        return $this->formatSpecification(
            static::SPECIFICATION_SELECT,
            $this->resolveTable($this->unaliasTable($this->table), $platform, $driver, $parameterContainer),
            $columns ? "({$columns})" : '',
            $selectSql,
        );
    }

    /**
     * Simple test for an associative array
     *
     * @link http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
     */
    /** @param array<array-key, mixed> $array */
    private function isAssocativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Overloading: variable retrieval
     * Retrieves value by column name
     *
     * @return ColumnValue
     * @throws Exception\InvalidArgumentException
     */
    public function __get(string $name): mixed
    {
        if (! array_key_exists($name, $this->columns)) {
            throw Exception\InvalidArgumentException::forUnknownColumnKey($name);
        }

        return $this->columns[$name];
    }

    /**
     * Overloading: variable isset
     *
     * Proxies to columns; does a column of that name exist?
     *
     * @return bool
     */
    public function __isset(string $name)
    {
        return array_key_exists($name, $this->columns);
    }

    /**
     * Overloading: variable setting
     *
     * Proxies to values, using VALUES_MERGE strategy
     *
     * @param ColumnValue $value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->columns[$name] = $value;
    }

    /**
     * Overloading: variable unset
     *
     * Proxies to values and columns
     *
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public function __unset(string $name)
    {
        if (! array_key_exists($name, $this->columns)) {
            throw Exception\InvalidArgumentException::forUnknownColumnKey($name);
        }

        unset($this->columns[$name]);
    }
}
