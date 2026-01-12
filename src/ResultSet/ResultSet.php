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

    private ResultSetReturnType $returnType;

    /** @var ArrayObject<string, mixed>|RowPrototypeInterface */
    private ArrayObject|RowPrototypeInterface $rowPrototype;

    /**
     * @param ArrayObject<string, mixed>|RowPrototypeInterface|null $rowPrototype
     */
    public function __construct(
        ResultSetReturnType|string $returnType = ResultSetReturnType::ArrayObject,
        ArrayObject|RowPrototypeInterface|null $rowPrototype = null
    ) {
        parent::__construct();
        $this->returnType   = is_string($returnType) ? ResultSetReturnType::from($returnType) : $returnType;
        $this->rowPrototype = $rowPrototype ?? new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * {@inheritDoc}
     *
     * @param ArrayObject<string, mixed>|RowPrototypeInterface $rowPrototype
     */
    #[Override]
    public function setRowPrototype(ArrayObject|RowPrototypeInterface $rowPrototype): ResultSetInterface
    {
        $this->rowPrototype = $rowPrototype;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return ArrayObject<string, mixed>|RowPrototypeInterface
     */
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
     *
     * @return array<string, mixed>|ArrayObject<string, mixed>|RowPrototypeInterface|null
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

        if (! is_array($data) && ! $data instanceof ArrayObject && ! $data instanceof RowPrototypeInterface) {
            return null;
        }

        return $data;
    }

    /**
     * Set the row object prototype
     *
     * @deprecated use setRowPrototype()
     *
     * @param ArrayObject<string, mixed>|RowPrototypeInterface $arrayObjectPrototype
     */
    public function setArrayObjectPrototype(ArrayObject|RowPrototypeInterface $arrayObjectPrototype): ResultSetInterface
    {
        return $this->setRowPrototype($arrayObjectPrototype);
    }

    /**
     * @deprecated use getRowPrototype()
     *
     * @return ArrayObject<string, mixed>|RowPrototypeInterface
     */
    public function getArrayObjectPrototype(): ArrayObject|RowPrototypeInterface
    {
        return $this->getRowPrototype();
    }
}
