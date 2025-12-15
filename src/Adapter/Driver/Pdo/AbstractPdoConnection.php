<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Override;
use PDO;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\RuntimeException;

use function is_array;
use function strtolower;

abstract class AbstractPdoConnection extends AbstractConnection implements
    PdoConnectionInterface,
    PdoDriverAwareInterface
{
    protected ?PdoDriverInterface $driver = null;

    protected ?string $dsn;

    /** @var ?PDO $resource */
    protected $resource;

    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        PDO|array $connectionParameters
    ) {
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);
        } elseif ($connectionParameters instanceof PDO) {
            $this->setResource($connectionParameters);
        }
    }

    #[Override]
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;

        return $this;
    }

    #[Override]
    public function setConnectionParameters(array $connectionParameters): ConnectionInterface
    {
        $this->connectionParameters = $connectionParameters;

        return $this;
    }

    /**
     * Get the dsn string for this connection
     *
     * @throws RuntimeException
     */
    #[Override]
    final public function getDsn(): string
    {
        if (! $this->dsn) {
            throw new Exception\RuntimeException(
                'The DSN has not been set or constructed from parameters in connect() for this Connection'
            );
        }

        return $this->dsn;
    }

    public function setResource(PDO $resource): PdoConnectionInterface
    {
        $this->resource   = $resource;
        $this->driverName = strtolower($this->resource->getAttribute(PDO::ATTR_DRIVER_NAME));

        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function isConnected(): bool
    {
        return $this->resource instanceof PDO;
    }

    /** {@inheritDoc} */
    #[Override]
    public function beginTransaction(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if (0 === $this->nestedTransactionsCount) {
            $this->resource->beginTransaction();
            $this->inTransaction = true;
        }

        $this->nestedTransactionsCount++;

        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function commit(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if ($this->inTransaction) {
            $this->nestedTransactionsCount -= 1;
        }

        /*
         * This shouldn't check for being in a transaction since
         * after issuing a SET autocommit=0; we have to commit too.
         */
        if (0 === $this->nestedTransactionsCount) {
            $this->resource->commit();
            $this->inTransaction = false;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function rollback(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback');
        }

        if (! $this->inTransaction()) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback');
        }

        $this->resource->rollBack();

        $this->inTransaction           = false;
        $this->nestedTransactionsCount = 0;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidQueryException
     */
    #[Override]
    public function execute($sql): ?ResultInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->profiler?->profilerStart($sql);

        $resultResource = $this->resource->query($sql);

        $this->profiler?->profilerFinish();

        if ($resultResource === false) {
            $errorInfo = $this->resource->errorInfo();
            throw new Exception\InvalidQueryException($errorInfo[2]);
        }

        /** @phpstan-ignore arguments.count */
        return $this->driver->createResult($resultResource, $sql);
    }

    /** Prepare a statement */
    public function prepare(?string $sql = null): StatementInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->driver->createStatement($sql);
    }
}
