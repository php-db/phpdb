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
use function str_replace;

class Insert extends AbstractPreparableSql
{
    /**
     * Constants
     *
     * @const
     */
    public const SPECIFICATION_INSERT = 'insert';

    final public const SPECIFICATION_SELECT = 'select';

    final public const VALUES_MERGE = 'merge';

    final public const VALUES_SET = 'set';

    /** @var string[]|array[] $specifications */
    protected array $specifications = [
        self::SPECIFICATION_INSERT => 'INSERT INTO %1$s (%2$s) VALUES (%3$s)',
        self::SPECIFICATION_SELECT => 'INSERT INTO %1$s %2$s %3$s',
    ];

    protected TableIdentifier|string|array $table = '';

    protected array $columns = [];

    protected null|array|Select $select = null;

    /**
     * Constructor
     */
    public function __construct(string|TableIdentifier|null $table = null)
    {
        if ($table) {
            $this->into($table);
        }
    }

    /**
     * Create INTO clause
     */
    public function into(TableIdentifier|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Specify columns
     */
    public function columns(array $columns): static
    {
        $this->columns = array_flip($columns);
        return $this;
    }

    /**
     * Specify values to insert
     *
     * @param string        $flag one of VALUES_MERGE or VALUES_SET; defaults to VALUES_SET
     * @throws Exception\InvalidArgumentException
     */
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

        if ($flag === self::VALUES_SET) {
            $this->columns = $this->isAssocativeArray($values)
                ? $values
                : array_combine(array_keys($this->columns), array_values($values));
        } else {
            foreach ($values as $column => $value) {
                $this->columns[$column] = $value;
            }
        }

        return $this;
    }

    /**
     * Simple test for an associative array
     *
     * @link http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
     */
    private function isAssocativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Create INTO SELECT clause
     */
    public function select(Select $select): static
    {
        return $this->values($select);
    }

    /**
     * Get raw state
     */
    public function getRawState(?string $key = null): TableIdentifier|string|array
    {
        $rawState = [
            'table'   => $this->table,
            'columns' => array_keys($this->columns),
            'values'  => array_values($this->columns),
        ];
        return $key !== null && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    protected function processInsert(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?string {
        if ($this->select) {
            return null;
        }

        if (! $this->columns) {
            throw new Exception\InvalidArgumentException('values or select should be present');
        }

        $columns     = [];
        $values      = [];
        $i           = 0;
        $isPdoDriver = $driver instanceof PdoDriverInterface;

        foreach ($this->columns as $column => $value) {
            $columns[] = $platform->quoteIdentifier($column);
            if (is_scalar($value) && $parameterContainer) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                if ($isPdoDriver) {
                    $column = 'c_' . $i++;
                }

                $values[] = $driver->formatParameterName($column);
                $parameterContainer->offsetSet($column, $value);
            } else {
                $values[] = $this->resolveColumnValue(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer
                );
            }
        }

        return str_replace(
            ['%1$s', '%2$s', '%3$s'],
            [
                $this->resolveTable($this->table, $platform, $driver, $parameterContainer),
                implode(', ', $columns),
                implode(', ', $values),
            ],
            $this->specifications[static::SPECIFICATION_INSERT]
        );
    }

    protected function processSelect(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?string {
        if (! $this->select) {
            return null;
        }

        $selectSql = $this->processSubSelect($this->select, $platform, $driver, $parameterContainer);

        $columns = array_map([$platform, 'quoteIdentifier'], array_keys($this->columns));
        $columns = implode(', ', $columns);

        return str_replace(
            ['%1$s', '%2$s', '%3$s'],
            [
                $this->resolveTable($this->table, $platform, $driver, $parameterContainer),
                $columns ? "({$columns})" : '',
                $selectSql,
            ],
            $this->specifications[static::SPECIFICATION_SELECT]
        );
    }

    /**
     * Overloading: variable setting
     *
     * Proxies to values, using VALUES_MERGE strategy
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
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        unset($this->columns[$name]);
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
     * Overloading: variable retrieval
     * Retrieves value by column name
     *
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function __get(string $name): mixed
    {
        if (! array_key_exists($name, $this->columns)) {
            throw new Exception\InvalidArgumentException(
                'The key ' . $name . ' was not found in this objects column list'
            );
        }

        return $this->columns[$name];
    }
}
