<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;

use function is_array;

class ResultSet extends AbstractResultSet
{
    /** @deprecated use ResultSetReturnType */
    public const TYPE_ARRAYOBJECT = 'arrayobject';
    public const TYPE_ARRAY       = 'array';

    public function __construct(
        private ResultSetReturnType|string $returnType = ResultSetReturnType::ArrayObject,
        private ArrayObject $objectPrototype = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS)
    ) {
        if (is_string($this->returnType)) {
            $this->returnType = ResultSetReturnType::from($this->returnType);
        }
    }

    /** {@inheritDoc} */
    #[Override]
    public function setObjectPrototype(ArrayObject $objectPrototype): ResultSetInterface
    {
        $this->objectPrototype = $objectPrototype;
        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getObjectPrototype(): ArrayObject
    {
        return $this->objectPrototype;
    }

    /**
     * Get the return type to use when returning objects from the set
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * Iterator: get current item
     */
    #[Override]
    public function current(): array|ArrayObject|null
    {
        $data = parent::current();

        if ($this->returnType === ResultSetReturnType::ArrayObject && is_array($data)) {
            $ao = clone $this->objectPrototype;
            $ao->exchangeArray($data);
            return $ao;
        }

        return $data;
    }

    /**
     * Set the row object prototype
     *
     * @deprecated use setObjectPrototype()
     */
    public function setArrayObjectPrototype(ArrayObject $arrayObjectPrototype): ResultSetInterface
    {
        return $this->setObjectPrototype($arrayObjectPrototype);
    }

    /**
     * @deprecated use getObjectPrototype()
     */
    public function getArrayObjectPrototype(): ArrayObject
    {
        return $this->getObjectPrototype();
    }
}
