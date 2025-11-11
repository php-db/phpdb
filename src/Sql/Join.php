<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use Override;
use ReturnTypeWillChange;

use function array_shift;
use function count;
use function is_array;
use function is_string;
use function key;
use function sprintf;

/**
 * Aggregate JOIN specifications.
 * Each specification is an array with the following keys:
 * - name: the JOIN name
 * - on: the table on which the JOIN occurs
 * - columns: the columns to include with the JOIN operation; defaults to
 *   `Select::SQL_STAR`.
 * - type: the type of JOIN being performed; see the `JOIN_*` constants;
 *   defaults to `JOIN_INNER`
 *
 * @implements Iterator
 * @implements Countable
 */
class Join implements Iterator, Countable
{
    public const JOIN_INNER       = 'inner';
    public const JOIN_OUTER       = 'outer';
    public const JOIN_FULL_OUTER  = 'full outer';
    public const JOIN_LEFT        = 'left';
    public const JOIN_RIGHT       = 'right';
    public const JOIN_RIGHT_OUTER = 'right outer';
    public const JOIN_LEFT_OUTER  = 'left outer';

    /**
     * Current iterator position.
     */
    private int $position = 0;

    /**
     * JOIN specifications
     */
    protected array $joins = [];

    /**
     * Initialize iterator position.
     */
    public function __construct()
    {
    }

    /**
     * Rewind iterator.
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Return current join specification.
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function current(): array
    {
        return $this->joins[$this->position];
    }

    /**
     * Return the current iterator index.
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Advance to the next JOIN specification.
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Is the iterator at a valid position?
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function valid(): bool
    {
        return isset($this->joins[$this->position]);
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param string|array|TableIdentifier $name A table name on which to join, or a single
     *     element associative array, of the form alias => table, or TableIdentifier instance
     * @param string|Predicate\Expression $on A specification describing the fields to join on.
     * @param string|string[]|int|int[] $columns A single column name, an array
     *     of column names, or (a) specification(s) such as SQL_STAR representing
     *     the columns to join.
     * @param string $type The JOIN type to use; see the JOIN_* constants.
     * @return $this Provides a fluent interface
     * @throws Exception\InvalidArgumentException For invalid $name values.
     */
    public function join($name, $on, $columns = [Select::SQL_STAR], $type = self::JOIN_INNER): static
    {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            throw new Exception\InvalidArgumentException(
                sprintf("join() expects '%s' as a single element associative array", array_shift($name))
            );
        }

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $this->joins[] = [
            'name'    => $name,
            'on'      => $on,
            'columns' => $columns,
            'type'    => $type ?: self::JOIN_INNER,
        ];

        return $this;
    }

    /**
     * Reset to an empty list of JOIN specifications.
     *
     * @return $this Provides a fluent interface
     */
    public function reset(): static
    {
        $this->joins = [];
        return $this;
    }

    /**
     * Get count of attached predicates
     */
    #[Override] #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->joins);
    }
}
