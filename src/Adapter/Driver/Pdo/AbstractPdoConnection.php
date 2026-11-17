<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOException;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\RuntimeException;

use function strtolower;

/**
 * @api
 *
 * @psalm-import-type ConnectionParameters from ConnectionInterface
 */
abstract class AbstractPdoConnection extends AbstractConnection implements
    PdoConnectionInterface,
    PdoDriverAwareInterface
{
    protected ?PdoDriverInterface $driver = null;

    protected ?string $dsn;

    /** @var ?PDO $resource */
    protected $resource;

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When connecting did not produce a PDO resource.
     * @throws PDOException When the driver cannot start a transaction.
     */
    #[Override]
    public function beginTransaction(): ConnectionInterface
    {
        $resource = $this->getConnectedResource();

        if (0 === $this->nestedTransactionsCount) {
            $resource->beginTransaction();
            $this->inTransaction = true;
        }

        $this->nestedTransactionsCount++;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When connecting did not produce a PDO resource.
     * @throws PDOException When the driver cannot commit.
     */
    #[Override]
    public function commit(): ConnectionInterface
    {
        $resource = $this->getConnectedResource();

        if ($this->inTransaction) {
            $this->nestedTransactionsCount -= 1;
        }

        /*
         * This shouldn't check for being in a transaction since
         * after issuing a SET autocommit=0; we have to commit too.
         */
        if (0 === $this->nestedTransactionsCount) {
            $resource->commit();
            $this->inTransaction = false;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * The SQL is passed to createResult() as a second argument for drivers that accept a
     * context there, such as the SQLite driver; DriverInterface declares only the first.
     *
     * @throws Exception\InvalidQueryException
     * @throws Exception\RuntimeException When connecting did not produce a PDO resource or no driver is set.
     * @throws PDOException When the driver raises one while running the query.
     *
     * @mago-expect analysis:too-many-arguments
     */
    #[Override]
    public function execute($sql): ?ResultInterface
    {
        $resource = $this->getConnectedResource();

        $this->profiler?->profilerStart($sql);

        $resultResource = $resource->query($sql);

        $this->profiler?->profilerFinish();

        if (false === $resultResource) {
            $errorInfo = $resource->errorInfo();
            throw Exception\InvalidQueryException::forDriverError($errorInfo[2]);
        }

        /** @phpstan-ignore arguments.count */
        return $this->getDriver()->createResult($resultResource, $sql);
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
            throw Exception\RuntimeException::forMissingDsn();
        }

        return $this->dsn;
    }

    /** {@inheritDoc} */
    #[Override]
    public function isConnected(): bool
    {
        return $this->resource instanceof PDO;
    }

    /**
     * Prepare a statement
     *
     * @throws Exception\RuntimeException When no driver is set.
     */
    public function prepare(?string $sql = null): StatementInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->getDriver()->createStatement($sql);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     * @throws PDOException When the driver cannot roll back.
     */
    #[Override]
    public function rollback(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            throw Exception\RuntimeException::forDisconnectedRollback();
        }

        if (! $this->inTransaction()) {
            throw Exception\RuntimeException::forRollbackWithoutTransaction();
        }

        $this->getConnectedResource()->rollBack();

        $this->inTransaction           = false;
        $this->nestedTransactionsCount = 0;

        return $this;
    }

    /** @param ConnectionParameters $connectionParameters */
    #[Override]
    public function setConnectionParameters(array $connectionParameters): ConnectionInterface
    {
        $this->connectionParameters = $connectionParameters;

        return $this;
    }

    #[Override]
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;

        return $this;
    }

    /** @throws PDOException When the driver name cannot be read from the resource. */
    public function setResource(PDO $resource): PdoConnectionInterface
    {
        $this->resource   = $resource;
        $this->driverName = strtolower((string) $resource->getAttribute(PDO::ATTR_DRIVER_NAME));

        return $this;
    }

    /**
     * Connect if needed and return the PDO resource.
     *
     * @throws Exception\RuntimeException When connecting did not produce a PDO resource.
     */
    private function getConnectedResource(): PDO
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->resource instanceof PDO
            ? $this->resource
            : throw Exception\RuntimeException::forMissingConnectionResource();
    }

    /** @throws Exception\RuntimeException When no driver is set. */
    private function getDriver(): PdoDriverInterface
    {
        return $this->driver ?? throw Exception\RuntimeException::forMissingDriver();
    }
}
