<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo\TestAsset;

use Override;
use PDO;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;

final class TestPdoWithFeatures extends AbstractPdo implements DriverFeatureProviderInterface
{
    use DriverFeatureProviderTrait;

    public function __construct(
        array|AbstractPdoConnection|PDO $connection,
        ?Statement $statement = null,
        ?Result $result = null,
        array $features = []
    ) {
        if (! $connection instanceof AbstractPdoConnection && ! $connection instanceof PDO) {
            $connection = new TestConnection($connection);
        }

        $this->connection         = $connection;
        $this->statementPrototype = $statement ?? new Statement();
        $this->resultPrototype    = $result ?? new Result();

        if (! $this->connection instanceof PDO) {
            $this->connection->setDriver($this);
        }

        $this->statementPrototype->setDriver($this);

        if ($features !== []) {
            $this->addFeatures($features);
        }
    }

    /** @param mixed $resource */
    #[Override]
    public function createResult($resource): Result
    {
        /** @var Result $result */
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue());
        return $result;
    }
}
