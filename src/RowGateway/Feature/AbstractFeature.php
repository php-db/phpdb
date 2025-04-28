<?php

namespace Laminas\Db\RowGateway\Feature;

use Laminas\Db\RowGateway\AbstractRowGateway;
use Laminas\Db\RowGateway\Exception;
use Laminas\Db\RowGateway\Exception\RuntimeException;
use Override;

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

    public function setRowGateway(AbstractRowGateway $rowGateway): void
    {
        $this->rowGateway = $rowGateway;
    }

    /**
     * @throws RuntimeException
     *
     * @return never
     */
    #[Override] public function initialize()
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
