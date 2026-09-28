<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use Override;
use PhpDb\TableGateway\AbstractTableGateway;

/**
 * @api
 */
abstract class AbstractFeature extends AbstractTableGateway implements FeatureInterface
{
    protected AbstractTableGateway $tableGateway;

    /** @var array<string, mixed> */
    protected array $sharedData = [];

    /** @return array<string, string[]> */
    #[Override]
    public function getMagicMethodSpecifications(): array
    {
        return [];
    }

    #[Override]
    public function getName(): string
    {
        return static::class;
    }

    #[Override]
    public function initialize(): void
    {
        // No-op
    }

    #[Override]
    public function setTableGateway(AbstractTableGateway $tableGateway): void
    {
        $this->tableGateway = $tableGateway;
    }

    /*
     * public function preInitialize();
     * public function postInitialize();
     * public function preSelect(Select $select);
     * public function postSelect(StatementInterface $statement, ResultInterface $result, ResultSetInterface $resultSet);
     * public function preInsert(Insert $insert);
     * public function postInsert(StatementInterface $statement, ResultInterface $result);
     * public function preUpdate(Update $update);
     * public function postUpdate(StatementInterface $statement, ResultInterface $result);
     * public function preDelete(Delete $delete);
     * public function postDelete(StatementInterface $statement, ResultInterface $result);
     */
}
