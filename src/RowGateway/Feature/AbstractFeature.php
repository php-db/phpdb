<?php

namespace PhpDb\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Exception;
use PhpDb\RowGateway\Exception\RuntimeException;

abstract class AbstractFeature extends AbstractRowGateway
{
    protected AbstractRowGateway $rowGateway;

    protected array $sharedData = [];

    public function getName(): string
    {
        return static::class;
    }

    public function setRowGateway(AbstractRowGateway $rowGateway): void
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

    public function getMagicMethodSpecifications(): array
    {
        return [];
    }
}
