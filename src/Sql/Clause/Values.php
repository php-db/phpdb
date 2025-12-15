<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

use function array_combine;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function range;

/**
 * @implements IteratorAggregate<string, mixed>
 */
final class Values implements Countable, IteratorAggregate
{
    protected array $values = [];

    public function columns(array $columns): static
    {
        $this->values = array_flip($columns);
        return $this;
    }

    public function set(array $values, string $flag = 'set'): static
    {
        if ($flag === 'set') {
            $this->values = $this->isAssociativeArray($values)
                ? $values
                : array_combine(array_keys($this->values), array_values($values));
        } else {
            foreach ($values as $column => $value) {
                $this->values[$column] = $value;
            }
        }

        return $this;
    }

    public function merge(string $column, mixed $value): static
    {
        $this->values[$column] = $value;
        return $this;
    }

    public function remove(string $column): static
    {
        unset($this->values[$column]);
        return $this;
    }

    public function has(string $column): bool
    {
        return isset($this->values[$column]) || array_key_exists($column, $this->values);
    }

    public function get(string $column): mixed
    {
        return $this->values[$column] ?? null;
    }

    public function getColumns(): array
    {
        return array_keys($this->values);
    }

    public function getValues(): array
    {
        return array_values($this->values);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->values);
    }

    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
