<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Countable;
use Iterator;
use IteratorAggregate;
use Laminas\Stdlib\PriorityList;

use function count;
use function is_numeric;
use function is_string;

/**
 * @implements IteratorAggregate<string, mixed>
 */
class Set implements Countable, IteratorAggregate
{
    protected PriorityList $values;

    public function __construct()
    {
        $this->values = new PriorityList();
        $this->values->isLIFO(false);
    }

    public function set(array $values, string|int $flag = 'set'): static
    {
        if ($flag === 'set') {
            $this->values->clear();
        }

        $priority = is_numeric($flag) ? (int) $flag : 0;

        foreach ($values as $column => $value) {
            if (! is_string($column)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $this->values->insert($column, $value, $priority);
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->values->toArray();
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Iterator
    {
        return $this->values;
    }
}
