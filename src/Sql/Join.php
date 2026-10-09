<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use Override;
use ReturnTypeWillChange;

use function array_key_exists;
use function array_shift;
use function count;
use function get_debug_type;
use function is_array;
use function is_string;
use function key;

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
 * @psalm-type JoinName = string|TableIdentifier|non-empty-array<string, string|TableIdentifier|Select|Expression>
 * @psalm-type JoinSpecification = array{
 *     name: JoinName,
 *     on: string|Predicate\PredicateInterface,
 *     columns: array<array-key, string|ExpressionInterface>,
 *     type: string,
 * }
 * @implements Iterator<int, JoinSpecification>
 */
class Join implements Iterator, Countable
{
    final public const JOIN_INNER = 'inner';

    final public const JOIN_OUTER = 'outer';

    final public const JOIN_FULL_OUTER = 'full outer';

    final public const JOIN_LEFT = 'left';

    final public const JOIN_RIGHT = 'right';

    final public const JOIN_RIGHT_OUTER = 'right outer';

    final public const JOIN_LEFT_OUTER = 'left outer';

    /**
     * Current iterator position.
     */
    private int $position = 0;

    /**
     * JOIN specifications
     *
     * @var list<JoinSpecification>
     */
    protected array $joins = [];

    /**
     * Get count of attached predicates
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->joins);
    }

    /**
     * Return current join specification.
     *
     * @return JoinSpecification
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function current(): array
    {
        return $this->joins[$this->position];
    }

    /** @return list<JoinSpecification> */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param JoinName                                                    $name    A table name on which
     *     to join, or a single element associative array, of the form alias => table, or TableIdentifier
     *     instance
     * @param string|Predicate\PredicateInterface                          $on      A specification
     *     describing the fields to join on.
     * @param string|array<array-key, string|ExpressionInterface>         $columns A single
     *     column name, an array of column names, or (a) specification(s) such as SQL_STAR representing
     *     the columns to join.
     * @param string                                                      $type    The JOIN type to use;
     *     see the JOIN_* constants.
     * @throws Exception\InvalidArgumentException For invalid $name values.
     */
    // phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
    public function join(
        array|string|TableIdentifier $name,
        string|Predicate\PredicateInterface $on,
        array|string $columns = [Select::SQL_STAR],
        string $type = self::JOIN_INNER,
    ): static {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            $first = array_shift($name);

            throw Exception\InvalidArgumentException::forInvalidJoinName(
                is_string($first) ? $first : get_debug_type($first),
            );
        }

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $this->joins[] = [
            'name'    => $name,
            'on'      => $on,
            'columns' => $columns,
            'type'    => $type,
        ];

        return $this;
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
     * Reset to an empty list of JOIN specifications.
     */
    public function reset(): static
    {
        $this->joins = [];
        return $this;
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
     * Is the iterator at a valid position?
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function valid(): bool
    {
        return array_key_exists($this->position, $this->joins);
    }
}
