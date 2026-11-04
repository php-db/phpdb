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
use function substr;

/**
 * @api
 *
 * @implements Iterator<array-key, mixed>
 * @implements ArrayAccess<array-key, mixed>
 */
class ParameterContainer implements Iterator, ArrayAccess, Countable
{
    public const string TYPE_AUTO    = 'auto';
    public const string TYPE_NULL    = 'null';
    public const string TYPE_DOUBLE  = 'double';
    public const string TYPE_INTEGER = 'integer';
    public const string TYPE_BINARY  = 'binary';
    public const string TYPE_STRING  = 'string';
    public const string TYPE_LOB     = 'lob';

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
     *
     * @param array<array-key, mixed> $data
     */
    public function __construct(array $data = [])
    {
        if ([] !== $data) {
            $this->setFromArray($data);
        }
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
     * Get errata iterator
     *
     * @return ArrayIterator<string, mixed>
     */
    public function getErrataIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errata);
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
     * Key
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function key(): int|string|null
    {
        return key($this->data);
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
     * Offset exists
     *
     * @param  string|int $name
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetExists(mixed $name): bool
    {
        return null !== ($this->data[$name] ?? null);
    }

    /**
     * Offset get
     *
     * A name that is not set is looked up again through the name mapping, without its
     * leading colon.
     *
     * @param  string|int $name
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $name): mixed
    {
        if (null !== ($this->data[$name] ?? null)) {
            return $this->data[$name];
        }

        if (! is_string($name)) {
            return null;
        }

        $mappedName = $this->nameMapping[ltrim($name, characters: ':')] ?? null;

        return null === $mappedName ? null : $this->data[$mappedName] ?? null;
    }

    /**
     * Offset get errata
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetGetErrata(string|int $name): mixed
    {
        $name = $this->requireDataName($name);

        return $this->errata[$name] ?? null;
    }

    /**
     * Offset get max length
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetGetMaxLength(string|int $name): mixed
    {
        $name = $this->requireDataName($name);

        return $this->maxLength[$name] ?? null;
    }

    /**
     * Offset has errata
     */
    public function offsetHasErrata(string|int $name): bool
    {
        $name = $this->nameAt($name);

        return null !== $name && null !== ($this->errata[$name] ?? null);
    }

    /**
     * Offset has max length
     */
    public function offsetHasMaxLength(string|int $name): bool
    {
        $name = $this->nameAt($name);

        return null !== $name && null !== ($this->maxLength[$name] ?? null);
    }

    /**
     * Offset set
     *
     * An integer name addresses an existing position, or adds a parameter named after it. A
     * string name may be given with a leading colon, and either form follows the name mapping.
     * A null name appends a parameter named after the current count. Any other name is rejected.
     *
     * @param mixed $name
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $name, mixed $value, mixed $errata = null, mixed $maxLength = null): void
    {
        $key = match (true) {
            is_int($name)    => $this->positions[$name] ?? (string) $name,
            is_string($name) => $this->nameMapping[str_starts_with($name, ':') ? substr($name, offset: 1) : $name]
                ?? $name,
            null === $name => (string) count($this->data),
            default => throw Exception\InvalidArgumentException::forInvalidKeyType(),
        };

        $isNewPosition = is_int($name)
            ? ! array_key_exists($name, $this->positions)
            : null === ($this->data[$key] ?? null);

        if (is_string($name) && is_string($value) && str_starts_with($value, ':')) {
            $this->nameMapping[substr($value, offset: 1)] = $key;
        }

        if ($isNewPosition) {
            $this->positions[] = $key;
        }

        $this->data[$key] = $value;

        if (null !== $errata) {
            $this->errata[$key] = $errata;
        }

        if (null !== $maxLength) {
            $this->maxLength[$key] = $maxLength;
        }
    }

    /**
     * Offset set errata
     *
     * @throws Exception\InvalidArgumentException When an integer name is not a known position.
     */
    public function offsetSetErrata(string|int $name, mixed $errata): void
    {
        $this->errata[$this->requireName($name)] = $errata;
    }

    /**
     * Offset set max length
     *
     * @throws Exception\InvalidArgumentException When an integer name is not a known position.
     */
    public function offsetSetMaxLength(string|int $name, mixed $maxLength): void
    {
        $this->maxLength[$this->requireName($name)] = $maxLength;
    }

    /**
     * Bind a parameter to another by reference. Referencing a name that is not set creates it
     * as null, so the two stay bound when either is set later.
     *
     * @mago-expect analysis:possibly-undefined-string-array-index
     */
    public function offsetSetReference(string|int $name, string|int $from): void
    {
        $this->data[$name] = &$this->data[$from];
    }

    /**
     * Offset unset
     *
     * An integer name addresses a position when one exists, and is used as the name otherwise.
     * The name keeps its position, so later positions do not shift.
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function offsetUnset(mixed $name): void
    {
        if (is_int($name)) {
            $name = $this->positions[$name] ?? $name;
        }
        unset($this->data[$name]);
    }

    /**
     * Offset unset errata
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetUnsetErrata(string|int $name): void
    {
        $name = $this->nameAt($name);
        if (null === $name || ! array_key_exists($name, $this->errata)) {
            throw Exception\InvalidArgumentException::forMissingData();
        }
        $this->errata[$name] = null;
    }

    /**
     * Offset unset max length
     *
     * @throws Exception\InvalidArgumentException
     */
    public function offsetUnsetMaxLength(string|int $name): void
    {
        $name = $this->nameAt($name);
        if (null === $name || ! array_key_exists($name, $this->maxLength)) {
            throw Exception\InvalidArgumentException::forMissingData();
        }
        $this->maxLength[$name] = null;
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

    /**
     * Set from array
     *
     * Values are parameter values of any type.
     *
     * @param array<array-key, mixed> $data
     *
     * @mago-expect analysis:mixed-assignment
     */
    public function setFromArray(array $data): static
    {
        foreach ($data as $name => $value) {
            $this->offsetSet($name, $value);
        }
        return $this;
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

    /** Resolve an integer name to the parameter name at that position; null when there is none. */
    private function nameAt(string|int $name): ?string
    {
        return is_int($name) ? $this->positions[$name] ?? null : $name;
    }

    /** @throws Exception\InvalidArgumentException When the name does not address a parameter. */
    private function requireDataName(string|int $name): string
    {
        $name = $this->requireName($name);

        return array_key_exists($name, $this->data)
            ? $name
            : throw Exception\InvalidArgumentException::forMissingData();
    }

    /** @throws Exception\InvalidArgumentException When an integer name is not a known position. */
    private function requireName(string|int $name): string
    {
        return $this->nameAt($name) ?? throw Exception\InvalidArgumentException::forMissingData();
    }
}
