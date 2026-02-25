<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\TableGateway\AbstractTableGateway;

abstract class AbstractFeature extends AbstractTableGateway implements FeatureInterface
{
    protected AbstractTableGateway $tableGateway;

    protected array $sharedData = [];

    public function getName(): string
    {
        return static::class;
    }

    public function setTableGateway(AbstractTableGateway $tableGateway): void
    {
        $this->tableGateway = $tableGateway;
    }

    public function initialize(): void
    {
        // No-op
    }

    /** @return array<string, string[]> */
    public function getMagicMethodSpecifications(): array
    {
        return [];
    }

    /*
    public function preInitialize();
    public function postInitialize();
    public function preSelect(Select $select);
    public function postSelect(StatementInterface $statement, ResultInterface $result, ResultSetInterface $resultSet);
    public function preInsert(Insert $insert);
    public function postInsert(StatementInterface $statement, ResultInterface $result);
    public function preUpdate(Update $update);
    public function postUpdate(StatementInterface $statement, ResultInterface $result);
    public function preDelete(Delete $delete);
    public function postDelete(StatementInterface $statement, ResultInterface $result);
    */
}
