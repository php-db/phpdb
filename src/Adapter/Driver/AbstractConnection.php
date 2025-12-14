<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use Override;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

abstract class AbstractConnection implements ConnectionInterface, ProfilerAwareInterface
{
    protected array $connectionParameters = [];

    protected ?string $driverName;

    protected bool $inTransaction = false;

    /** Nested transactions count. */
    protected int $nestedTransactionsCount = 0;

    protected ?ProfilerInterface $profiler = null;

    /**
     * Extending classes must be covariant
     *
     * @var mixed
     */
    protected $resource;

    #[Override]
    public function disconnect(): ConnectionInterface
    {
        if ($this->isConnected()) {
            $this->resource = null;
        }

        return $this;
    }

    /** Get connection parameters */
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
     * @return resource|null
     */
    #[Override]
    public function getResource()
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
