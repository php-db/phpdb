<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;

use function in_array;
use function is_array;
use function method_exists;

class ResultSet extends AbstractResultSet
{
    public const TYPE_ARRAYOBJECT = 'arrayobject';

    public const TYPE_ARRAY = 'array';

    /**
     * Allowed return types
     */
    protected array $allowedReturnTypes = [
        self::TYPE_ARRAYOBJECT,
        self::TYPE_ARRAY,
    ];

    protected ArrayObject $arrayObjectPrototype;

    /**
     * Return type to use when returning an object from the set
     */
    protected string $returnType = self::TYPE_ARRAYOBJECT;

    /**
     * Constructor
     */
    public function __construct(string $returnType = self::TYPE_ARRAYOBJECT, ?ArrayObject $arrayObjectPrototype = null)
    {
        $this->returnType = in_array($returnType, $this->allowedReturnTypes, true) ?
            $returnType : self::TYPE_ARRAYOBJECT;

        if ($this->returnType === self::TYPE_ARRAYOBJECT) {
            $this->setArrayObjectPrototype($arrayObjectPrototype ?: new ArrayObject([], ArrayObject::ARRAY_AS_PROPS));
        }
    }

    /**
     * Set the row object prototype
     *
     * @throws Exception\InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    public function setArrayObjectPrototype(ArrayObject $arrayObjectPrototype): static
    {
        if (! method_exists($arrayObjectPrototype, 'exchangeArray')) {
            throw new Exception\InvalidArgumentException(
                'Object must at least implement exchangeArray'
            );
        }

        $this->arrayObjectPrototype = $arrayObjectPrototype;
        return $this;
    }

    public function setObjectPrototype(ArrayObject $objectPrototype): static
    {
        if (! $objectPrototype instanceof ArrayObject) {
            throw new Exception\InvalidArgumentException(
                'Object prototype must be an instance of ArrayObject'
            );
        }
        return $this->setArrayObjectPrototype($objectPrototype);
    }

    /**
     * Get the row object prototype
     */
    public function getArrayObjectPrototype(): ArrayObject
    {
        return $this->arrayObjectPrototype;
    }

    /**
     * Get the return type to use when returning objects from the set
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function current(): array|object|null
    {
        $data = parent::current();

        if ($this->returnType === self::TYPE_ARRAYOBJECT && is_array($data)) {
            $ao = clone $this->arrayObjectPrototype;
            $ao->exchangeArray($data);

            return $ao;
        }

        return $data;
    }
}
