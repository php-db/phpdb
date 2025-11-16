<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use Override;
use ReturnTypeWillChange;

use function array_key_exists;
use function array_values;
use function count;
use function current;
use function is_int;
use function is_string;
use function key;
use function ltrim;
use function next;
use function reset;
use function str_starts_with;

class ParameterContainer implements Iterator, ArrayAccess, Countable
{
    public const TYPE_AUTO    = 'auto';
    public const TYPE_NULL    = 'null';
    public const TYPE_DOUBLE  = 'double';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BINARY  = 'binary';
    public const TYPE_STRING  = 'string';
    public const TYPE_LOB     = 'lob';

    /**
     * Data
     *
     * @var array<string|int, mixed>
     */
    protected array $data = [];

    /** @var array<int, string> */
    protected array $positions = [];

    /**
     * Errata
     *
     * @var array<string, mixed>
     */
    protected array $errata = [];

    /**
     * Max length
     *
     * @var array<string, mixed>
     */
    protected array $maxLength = [];

    /** @var array<string, string> */
    protected array $nameMapping = [];

    /**
     * Constructor
     */
    public function __construct(array $data = [])
    {
        if ($data !== []) {
            $this->setFromArray($data);
        }
    }

    /**
     * Offset exists
     *
     * @param  string|int $name
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetExists(mixed $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Offset get
     *
     * @param  string|int $name
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $name): mixed
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        $normalizedName = ltrim($name, ':');
        if (
            isset($this->nameMapping[$normalizedName])
            && isset($this->data[$this->nameMapping[$normalizedName]])
        ) {
            return $this->data[$this->nameMapping[$normalizedName]];
        }

        return null;
    }

    public function offsetSetReference(string|int $name, string|int $from): void
    {
        $this->data[$name] = &$this->data[$from];
    }

    /**
     * Offset set
     *
     * @param string|int|null $name
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $name, mixed $value, mixed $errata = null, mixed $maxLength = null): void
    {
        $position = false;

        // if integer, get name for this position
        if (is_int($name)) {
            if (isset($this->positions[$name])) {
                $position = $name;
                $name     = $this->positions[$name];
            } else {
                $name = (string) $name;
            }
        } elseif (is_string($name)) {
            // is a string:
            $normalizedName = ltrim($name, ':');
            if (isset($this->nameMapping[$normalizedName])) {
                // We have a mapping; get real name from it
                $name = $this->nameMapping[$normalizedName];
            }

            $position = array_key_exists($name, $this->data);

            // @todo: this assumes that any data begining with a ":" will be considered a parameter
            if (is_string($value) && str_starts_with($value, ':')) {
                // We have a named parameter; handle name mapping (container creation)
                $this->nameMapping[ltrim($value, ':')] = $name;
            }
        } elseif ($name === null) {
            $name = (string) count($this->data);
        } else {
            throw new Exception\InvalidArgumentException('Keys must be string, integer or null');
        }

        if ($position === false) {
            $this->positions[] = $name;
        }

        $this->data[$name] = $value;

        if ($errata) {
            $this->offsetSetErrata($name, $errata);
        }

        if ($maxLength) {
            $this->offsetSetMaxLength($name, $maxLength);
        }
    }

    /**
     * Offset unset
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetUnset(mixed $name): void
    {
        if (is_int($name) && isset($this->positions[$name])) {
            $name = $this->positions[$name];
        }
        unset($this->data[$name]);
    }

    /**
     * Set from array
     *
     * @return $this Provides a fluent interface
     */
    public function setFromArray(array $data): static
    {
        foreach ($data as $n => $v) {
            $this->offsetSet($n, $v);
        }
        return $this;
    }

    /**
     * Offset set max length
     */
    public function offsetSetMaxLength(string|int $name, mixed $maxLength): void
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        $this->maxLength[$name] = $maxLength;
    }

    /**
     * Offset get max length
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetGetMaxLength(string|int $name): mixed
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        if (! array_key_exists($name, $this->data)) {
            throw new Exception\InvalidArgumentException('Data does not exist for this name/position');
        }
        return $this->maxLength[$name];
    }

    /**
     * Offset has max length
     */
    public function offsetHasMaxLength(string|int $name): bool
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        return isset($this->maxLength[$name]);
    }

    /**
     * Offset unset max length
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetUnsetMaxLength(string|int $name): void
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        if (! array_key_exists($name, $this->maxLength)) {
            throw new Exception\InvalidArgumentException('Data does not exist for this name/position');
        }
        $this->maxLength[$name] = null;
    }

    /**
     * Get max length iterator
     *
     * @return ArrayIterator<string, mixed>
     */
    public function getMaxLengthIterator(): ArrayIterator
    {
        return new ArrayIterator($this->maxLength);
    }

    /**
     * Offset set errata
     */
    public function offsetSetErrata(string|int $name, mixed $errata): void
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        $this->errata[$name] = $errata;
    }

    /**
     * Offset get errata
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetGetErrata(string|int $name): mixed
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        if (! array_key_exists($name, $this->data)) {
            throw new Exception\InvalidArgumentException('Data does not exist for this name/position');
        }
        return $this->errata[$name];
    }

    /**
     * Offset has errata
     */
    public function offsetHasErrata(string|int $name): bool
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        return isset($this->errata[$name]);
    }

    /**
     * Offset unset errata
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetUnsetErrata(string|int $name): void
    {
        if (is_int($name)) {
            $name = $this->positions[$name];
        }
        if (! array_key_exists($name, $this->errata)) {
            throw new Exception\InvalidArgumentException('Data does not exist for this name/position');
        }
        $this->errata[$name] = null;
    }

    /**
     * Get errata iterator
     *
     * @return ArrayIterator<string, mixed>
     */
    public function getErrataIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errata);
    }

    /**
     * getNamedArray
     *
     * @return array<string|int, mixed>
     */
    public function getNamedArray(): array
    {
        return $this->data;
    }

    /**
     * getNamedArray
     *
     * @return array<int, mixed>
     */
    public function getPositionalArray(): array
    {
        return array_values($this->data);
    }

    /**
     * count
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Current
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function current(): mixed
    {
        return current($this->data);
    }

    /**
     * Next
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function next(): void
    {
        next($this->data);
    }

    /**
     * Key
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function key(): int|string|null
    {
        return key($this->data);
    }

    /**
     * Valid
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function valid(): bool
    {
        return current($this->data) !== false;
    }

    /**
     * Rewind
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function rewind(): void
    {
        reset($this->data);
    }
}
