<?php

namespace PhpDb\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Exception;
use PhpDb\RowGateway\Exception\RuntimeException;

abstract class AbstractFeature extends AbstractRowGateway
{
    /** @var AbstractRowGateway */
    protected $rowGateway;

    /** @var array */
    protected $sharedData = [];

    /**
     * @return string
     */
    public function getName()
    {
        return static::class;
    }

    public function setRowGateway(AbstractRowGateway $rowGateway)
    {
        $this->rowGateway = $rowGateway;
    }

    /**
     * @throws RuntimeException
     */
    public function initialize(): void
    {
        throw new Exception\RuntimeException('This method is not intended to be called on this object.');
    }

    /**
     * @return array
     */
    public function getMagicMethodSpecifications()
    {
        return [];
    }
}
