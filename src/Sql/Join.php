<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\PreparableSqlInterface;
use Countable;
use Iterator;
use Override;
use ReturnTypeWillChange;

use function array_shift;
use function count;
use function implode;
use function is_array;
use function is_string;
use function key;
use function preg_replace;
use function sprintf;
use function strtoupper;

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
     */
    protected array $joins = [];

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
     * @param array|string|TableIdentifier $name    A table name on which to join, or a single
     *     element associative array, of the form alias => table, or TableIdentifier instance
     * @param string|Predicate\Expression  $on      A specification describing the fields to join on.
     * @param int|string|int[]|string[]    $columns A single column name, an array
     *     of column names, or (a) specification(s) such as SQL_STAR representing
     *     the columns to join.
     * @param string                       $type    The JOIN type to use; see the JOIN_* constants.
     * @throws Exception\InvalidArgumentException For invalid $name values.
     */
    // phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
    public function join(
        array|string|TableIdentifier $name,
        string|Predicate\PredicateInterface $on,
        array|int|string $columns = [Select::SQL_STAR],
        string $type = self::JOIN_INNER
    ): static {
        if (is_array($name) && (! is_string(key($name)) || count($name) !== 1)) {
            throw new Exception\InvalidArgumentException(
                sprintf("join() expects '%s' as a single element associative array", array_shift($name))
            );
        }

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        // Convert to TableIdentifier for consistent handling
        $tableIdentifier = TableIdentifier::from($name);

        $this->joins[] = [
            'name'    => $tableIdentifier,
            'on'      => $on,
            'columns' => $columns,
            'type'    => $type,
        ];

        return $this;
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
     * Get count of attached predicates
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->joins);
    }

    /**
     * Build SQL part string with marker-based identifiers.
     * Returns empty string if no joins, otherwise returns " INNER JOIN ... ON ..." etc.
     */
    public function toSqlPart(array &$values): string
    {
        if ($this->joins === []) {
            return '';
        }

        $lq = PreparableSqlInterface::P_LQUOTE;
        $rq = PreparableSqlInterface::P_RQUOTE;

        $parts = [];
        foreach ($this->joins as $join) {
            /** @var TableIdentifier $tableIdentifier */
            $tableIdentifier = $join['name'];

            // TableIdentifier handles table name, schema, and alias via toSqlPart()
            $sql = strtoupper($join['type']) . ' JOIN ' . $tableIdentifier->toSqlPart();

            // Process ON condition
            if ($join['on'] instanceof Predicate\PredicateInterface) {
                $sql .= ' ON ' . $join['on']->toSqlPart($values);
            } else {
                // String ON condition - add markers for identifiers (table.column pattern)
                $sql .= ' ON ' . preg_replace(
                    '/([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)/',
                    $lq . '$1' . $rq . '.' . $lq . '$2' . $rq,
                    $join['on']
                );
            }

            $parts[] = $sql;
        }

        return ' ' . implode(' ', $parts);
    }
}
