<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Override;

use function is_array;
use function is_string;

class ResultSet extends AbstractResultSet implements ArrayObjectResultSetInterface
{
    /** @deprecated use ResultSetReturnType */
    public const TYPE_ARRAYOBJECT = 'arrayobject';
    public const TYPE_ARRAY       = 'array';

    private ResultSetReturnType $returnType;

    public function __construct(
        ResultSetReturnType|string $returnType = ResultSetReturnType::ArrayObject,
        private ArrayObject $rowPrototype = new ArrayObject(
            [],
            ArrayObject::ARRAY_AS_PROPS,
        ),
    ) {
        $this->returnType = is_string($returnType) ? ResultSetReturnType::from($returnType) : $returnType;
    }

    /**
     * Iterator: get current item
     */
    #[Override]
    public function current(): array|object|null
    {
        $data = parent::current();

        if (ResultSetReturnType::ArrayObject === $this->returnType && is_array($data)) {
            $ao = clone $this->getRowPrototype();
            $ao->exchangeArray($data);

            return $ao;
        }

        return $data;
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
        $this->rowPrototype = $rowPrototype;

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
}
