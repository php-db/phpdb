<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Override;

use function is_array;
use function is_string;

/**
 * @api
 *
 * @extends AbstractResultSet<array<array-key, mixed>|ArrayObject>
 */
class ResultSet extends AbstractResultSet implements ArrayObjectResultSetInterface
{
    /** @deprecated use ResultSetReturnType */
    public const string TYPE_ARRAYOBJECT = 'arrayobject';
    public const string TYPE_ARRAY       = 'array';

    private readonly ResultSetReturnType $returnType;

    /**
     * Whether the selected return type fills the row prototype, resolved once.
     *
     * The match is exhaustive so that a return type added without a decision here
     * fails at construction rather than falling through current() unnoticed.
     */
    private readonly bool $fillsRowPrototype;

    /**
     * getRowPrototype(), asked once per data source rather than once per row, so that a
     * subclass overriding the getter is honoured without a method call on every row.
     */
    private ?ArrayObject $resolvedRowPrototype = null;

    public function __construct(
        ResultSetReturnType|string $returnType = ResultSetReturnType::ArrayObject,
        private ArrayObject $rowPrototype = new ArrayObject(
            [],
            ArrayObject::ARRAY_AS_PROPS,
        ),
    ) {
        $this->returnType        = is_string($returnType) ? ResultSetReturnType::from($returnType) : $returnType;
        $this->fillsRowPrototype = match ($this->returnType) {
            ResultSetReturnType::ArrayObject, ResultSetReturnType::Prototype => true,
            ResultSetReturnType::Array                                       => false,
        };
    }

    /**
     * Iterator: get current item
     *
     * @return array<array-key, mixed>|ArrayObject|null
     *
     * @throws Exception\RuntimeException
     * @throws Exception\ValueError If a row is not row data.
     */
    #[Override]
    public function current(): array|ArrayObject|null
    {
        return $this->currentRow();
    }

    /**
     * @deprecated use getRowPrototype()
     */
    public function getArrayObjectPrototype(): ArrayObject
    {
        return $this->getRowPrototype();
    }

    /**
     * Get the return type to use when returning objects from the set
     */
    public function getReturnType(): ResultSetReturnType
    {
        return $this->returnType;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): ArrayObject
    {
        return $this->rowPrototype;
    }

    /**
     * Set the row object prototype
     *
     * @deprecated use setRowPrototype()
     */
    public function setArrayObjectPrototype(
        ArrayObject $arrayObjectPrototype,
    ): ResultSetInterface&ArrayObjectResultSetInterface {
        return $this->setRowPrototype($arrayObjectPrototype);
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(
        ArrayObject $rowPrototype,
    ): ResultSetInterface&ArrayObjectResultSetInterface {
        $this->rowPrototype         = $rowPrototype;
        $this->resolvedRowPrototype = $this->getRowPrototype();

        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            $return[] = $row instanceof ArrayObject ? $row->getArrayCopy() : $row;
        }

        return $return;
    }

    /**
     * An ArrayObject row is the caller's own object and is passed through untouched;
     * row data fills the prototype when the return type calls for it.
     *
     * @return array<array-key, mixed>|ArrayObject
     *
     * @throws Exception\ValueError If the row is neither an ArrayObject nor row data.
     */
    #[Override]
    protected function mapRow(mixed $row): array|ArrayObject
    {
        if (! is_array($row)) {
            if ($row instanceof ArrayObject) {
                return $row;
            }

            $row = $this->getArrayData($row);
        }

        if (! $this->fillsRowPrototype) {
            return $row;
        }

        /** @var ArrayObject $prototype Resolved by initialize() before any row is read. */
        $prototype = $this->resolvedRowPrototype;

        $ao = clone $prototype;
        $ao->exchangeArray($row);

        return $ao;
    }

    #[Override]
    protected function resetResolvedConfiguration(): void
    {
        $this->resolvedRowPrototype = $this->getRowPrototype();
    }
}
