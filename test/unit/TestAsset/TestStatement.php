<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\Pgsql\Pgsql;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler;
use Override;

class TestStatement implements StatementInterface
{
    protected ?DriverInterface $driver = null;

    protected ?Profiler\ProfilerInterface $profiler = null;

    protected ?string $sql = '';

    protected ?ParameterContainer $parameterContainer = null;

    protected mixed $resource = null;

    /**
     * @return $this Provides a fluent interface
     */
    public function setDriver(Pgsql $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setProfiler(Profiler\ProfilerInterface $profiler): self
    {
        $this->profiler = $profiler;
        return $this;
    }
    /**
     * Set sql
     *
     * @param null|string $sql
     * @return static
     */
    #[Override]
    public function setSql($sql): self
    {
        $this->sql = $sql;
        return $this;
    }

    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): self
    {
        return $this;
    }

    #[Override]
    public function getParameterContainer()
    {
        // TODO: Implement getParameterContainer() method.
    }

    #[Override]
    public function getResource(): bool
    {
        return (bool) $this->resource;
    }

    #[Override]
    public function prepare($sql = null): void
    {
        // TODO: Implement prepare() method.
    }

    #[Override]
    /**
     * Is prepared
     *
     * @return bool
     */
    public function isPrepared(): bool
    {
        return isset($this->resource);
    }
}
