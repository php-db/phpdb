<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PhpDb\Sql\Exception;
use Traversable;

use function count;
use function is_string;

/**
 * @implements IteratorAggregate<string, mixed>
 */
final class Set implements Countable, IteratorAggregate
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function set(array $values, string|int $flag = 'set'): static
    {
        if ($flag === 'set') {
            $this->values = [];
        }

        foreach ($values as $column => $value) {
            if (! is_string($column)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $this->values[$column] = $value;
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }
}
