<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Override;

use function is_array;
use function is_string;

class ResultSet extends AbstractResultSet
{
    /** @deprecated use ResultSetReturnType */
    public const TYPE_ARRAYOBJECT = 'arrayobject';
    public const TYPE_ARRAY       = 'array';

    public function __construct(
        private ResultSetReturnType|string $returnType = ResultSetReturnType::ArrayObject,
        private ArrayObject|RowPrototypeInterface|null $rowPrototype = new ArrayObject(
            [],
            ArrayObject::ARRAY_AS_PROPS
        )
    ) {
        if (is_string($this->returnType)) {
            $this->returnType = ResultSetReturnType::from($this->returnType);
        }
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(ArrayObject|RowPrototypeInterface $rowPrototype): ResultSetInterface
    {
        $this->rowPrototype = $rowPrototype;

        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): ArrayObject|RowPrototypeInterface
    {
        return $this->rowPrototype;
    }

    /**
     * Get the return type to use when returning objects from the set
     */
    public function getReturnType(): ResultSetReturnType
    {
        return $this->returnType;
    }

    /**
     * Iterator: get current item
     */
    #[Override]
    public function current(): array|ArrayObject|RowPrototypeInterface|null
    {
        $data = parent::current();

        if ($this->returnType === ResultSetReturnType::ArrayObject && is_array($data)) {
            $ao = clone $this->getRowPrototype();
            $ao->exchangeArray($data);

            return $ao;
        }

        return $data;
    }

    /**
     * Set the row object prototype
     *
     * @deprecated use setRowPrototype()
     */
    public function setArrayObjectPrototype(ArrayObject|RowPrototypeInterface $arrayObjectPrototype): ResultSetInterface
    {
        return $this->setRowPrototype($arrayObjectPrototype);
    }

    /**
     * @deprecated use getRowPrototype()
     */
    public function getArrayObjectPrototype(): ArrayObject|RowPrototypeInterface
    {
        return $this->getRowPrototype();
    }
}
