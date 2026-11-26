<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use Override;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

/**
 * @api
 *
 * @template TConnection of object|resource = object|resource The native connection handle
 *
 * @implements ConnectionInterface<TConnection>
 *
 * @psalm-import-type ConnectionParameters from ConnectionInterface
 */
abstract class AbstractConnection implements ConnectionInterface, ProfilerAwareInterface
{
    /** @var ConnectionParameters */
    protected array $connectionParameters = [];

    protected ?string $driverName;

    protected bool $inTransaction = false;

    /** Nested transactions count. */
    protected int $nestedTransactionsCount = 0;

    protected ?ProfilerInterface $profiler = null;

    /**
     * The driver's native connection handle, or null while disconnected. It is typed mixed
     * because a native extension may hand back a resource, which has no declarable type, and
     * property types are invariant, so a driver narrows it where it reads it, not by
     * redeclaring it.
     *
     * @var TConnection|null
     */
    protected mixed $resource = null;

    #[Override]
    public function disconnect(): ConnectionInterface
    {
        if ($this->isConnected()) {
            $this->resource = null;
        }

        return $this;
    }

    /**
     * Get connection parameters
     *
     * @return ConnectionParameters
     */
    #[Override]
    public function getConnectionParameters(): array
    {
        return $this->connectionParameters;
    }

    /** Get driver name */
    public function getDriverName(): ?string
    {
        return $this->driverName;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * @return TConnection|null
     */
    #[Override]
    public function getResource(): mixed
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->resource;
    }

    /** Checks whether the connection is in transaction state. */
    #[Override]
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /** @param ConnectionParameters $connectionParameters */
    #[Override]
    public function setConnectionParameters(array $connectionParameters): ConnectionInterface
    {
        $this->connectionParameters = $connectionParameters;

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;

        return $this;
    }
}
