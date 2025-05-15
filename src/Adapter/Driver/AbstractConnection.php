<?php

namespace Laminas\Db\Adapter\Driver;

use Laminas\Db\Adapter\Profiler\ProfilerAwareInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Override;

abstract class AbstractConnection implements ConnectionInterface, ProfilerAwareInterface
{
    protected array $connectionParameters = [];

    protected ?string $driverName;

    protected bool $inTransaction = false;

    /** Nested transactions count. */
    protected int $nestedTransactionsCount = 0;

    protected ?ProfilerInterface $profiler;

    /** @var resource|null */
    protected $resource;

    #[Override]
    public function disconnect(): static
    {
        if ($this->isConnected()) {
            $this->resource = null;
        }

        return $this;
    }

    /** Get connection parameters */
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

    #[Override]
    public function getResource()
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->resource;
    }

    /** Checks whether the connection is in transaction state. */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function setConnectionParameters(array $connectionParameters): static
    {
        $this->connectionParameters = $connectionParameters;

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function setProfiler(ProfilerInterface $profiler): static
    {
        $this->profiler = $profiler;

        return $this;
    }
}
