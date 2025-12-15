<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use Countable;
use Iterator;
use Override;
use PhpDb\Sql\ClauseInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function array_shift;
use function count;
use function is_array;
use function is_string;
use function key;
use function sprintf;

/**
 * Aggregate JOIN specifications.
 *
 * @implements Iterator<int, JoinSpecification>
 */
final class Join implements Iterator, Countable, ClauseInterface
{
    public const JOIN_INNER       = 'inner';
    public const JOIN_OUTER       = 'outer';
    public const JOIN_FULL_OUTER  = 'full outer';
    public const JOIN_LEFT        = 'left';
    public const JOIN_RIGHT       = 'right';
    public const JOIN_RIGHT_OUTER = 'right outer';
    public const JOIN_LEFT_OUTER  = 'left outer';

    private int $position = 0;

    /** @var JoinSpecification[] */
    protected array $joins = [];

    #[Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[Override]
    public function current(): JoinSpecification
    {
        return $this->joins[$this->position];
    }

    #[Override]
    public function key(): int
    {
        return $this->position;
    }

    #[Override]
    public function next(): void
    {
        ++$this->position;
    }

    #[Override]
    public function valid(): bool
    {
        return isset($this->joins[$this->position]);
    }

    /** @return JoinSpecification[] */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param array|string|TableIdentifier $name    A table name on which to join
     * @param string|Predicate\PredicateInterface $on A specification describing the fields to join on
     * @param array|int|string $columns Columns to include with the JOIN
     * @param string $type The JOIN type to use
     * @throws Exception\InvalidArgumentException For invalid $name values.
	 * @phpcs:disable Squiz.Classes.SelfMemberReference.NotUsedSelfClass
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

        if (is_string($on)) {
            $on = new Predicate\Expression($on);
        }

        $this->joins[] = new JoinSpecification(
            TableIdentifier::from($name),
            $on,
            $columns,
            JoinType::fromString($type),
        );

        return $this;
    }

    public function reset(): static
    {
        $this->joins = [];
        return $this;
    }

    #[Override]
    public function count(): int
    {
        return count($this->joins);
    }

    /**
     * Build SQL part string with quoted identifiers.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if ($this->joins === []) {
            return '';
        }

        $sql = '';
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join->type->value . ' JOIN ' . $join->name->prepareSqlString($builder)
                  . ' ON ' . $join->on->prepareSqlString($builder);
        }

        return $sql;
    }

    /**
     * Build columns SQL for SELECT clause from all joins.
     */
    public function toColumnsSqlPart(PreparableSqlBuilder $builder): string
    {
        $result = '';
        foreach ($this->joins as $join) {
            $result .= $join->toColumnsSql($builder);
        }
        return $result;
    }
}
