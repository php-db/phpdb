<?php

namespace Laminas\Db\Adapter\Driver\Pdo;

use Laminas\Db\Adapter\Driver\PdoDriverAwareInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler;
use Override;
use PDO;
use PDOException;
use PDOStatement;

use function implode;
use function is_array;
use function is_bool;
use function is_int;

class Statement implements StatementInterface, PdoDriverAwareInterface, Profiler\ProfilerAwareInterface
{
    /** @var PDO */
    protected $pdo;

    /** @var Profiler\ProfilerInterface */
    protected $profiler;

    /** @var PdoDriverInterface */
    protected $driver;

    /** @var string */
    protected $sql = '';

    /** @var bool */
    protected $isQuery;

    /** @var ParameterContainer */
    protected $parameterContainer;

    /** @var bool */
    protected $parametersBound = false;

    /** @var PDOStatement */
    protected $resource;

    /** @var bool */
    protected $isPrepared = false;

    /**
     * Set driver
     *
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setDriver(PdoDriverInterface $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getProfiler(): ?Profiler\ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setProfiler(Profiler\ProfilerInterface $profiler): static
    {
        $this->profiler = $profiler;

        return $this;
    }

    /**
     * Initialize
     *
     * @return $this Provides a fluent interface
     */
    public function initialize(PDO $connectionResource)
    {
        $this->pdo = $connectionResource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @return $this Provides a fluent interface
     */
    public function setResource(PDOStatement $pdoStatement)
    {
        $this->resource = $pdoStatement;

        return $this;
    }

    /**
     * Perform a deep clone
     *
     * @return void
     */
    public function __clone()
    {
        $this->isPrepared      = false;
        $this->parametersBound = false;
        $this->resource        = null;
        if ($this->parameterContainer) {
            $this->parameterContainer = clone $this->parameterContainer;
        }
    }

    /**
     * Bind parameters from container
     */
    protected function bindParametersFromContainer()
    {
        if ($this->parametersBound) {
            return;
        }

        $parameters = $this->parameterContainer->getNamedArray();
        foreach ($parameters as $name => &$value) {
            if (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_int($value)) {
                $type = PDO::PARAM_INT;
            } else {
                $type = PDO::PARAM_STR;
            }
            if ($this->parameterContainer->offsetHasErrata($name)) {
                switch ($this->parameterContainer->offsetGetErrata($name)) {
                    case ParameterContainer::TYPE_INTEGER:
                        $type = PDO::PARAM_INT;
                        break;
                    case ParameterContainer::TYPE_NULL:
                        $type = PDO::PARAM_NULL;
                        break;
                    case ParameterContainer::TYPE_LOB:
                        $type = PDO::PARAM_LOB;
                        break;
                }
            }

            // parameter is named or positional, value is reference
            $parameter = is_int($name) ? $name + 1 : $this->driver->formatParameterName($name);
            $this->resource->bindParam($parameter, $value, $type);
        }
    }

    /**
     * Set sql
     *
     * @param string $sql
     * @return $this Provides a fluent interface
     */
    public function setSql($sql): static
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     * Get sql
     *
     * @return string
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setParameterContainer(ParameterContainer $parameterContainer)
    {
        $this->parameterContainer = $parameterContainer;

        return $this;
    }

    /**
     * @return ParameterContainer
     */
    public function getParameterContainer()
    {
        return $this->parameterContainer;
    }

    /**
     * @param string $sql
     * @throws Exception\RuntimeException
     */
    public function prepare(?string $sql = null): StatementInterface
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException('This statement has been prepared already');
        }

        if ($sql === null) {
            $sql = $this->sql;
        }

        $this->resource = $this->pdo->prepare($sql);

        if ($this->resource === false) {
            $error = $this->pdo->errorInfo();
            throw new Exception\RuntimeException($error[2]);
        }

        $this->isPrepared = true;

        return $this;
    }

    public function isPrepared(): bool
    {
        return $this->isPrepared;
    }

    /** @throws Exception\InvalidQueryException */
    public function execute(null|array|ParameterContainer $parameters = null): ?ResultInterface
    {
        if (! $this->isPrepared) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if (! $this->parameterContainer instanceof ParameterContainer) {
            if ($parameters instanceof ParameterContainer) {
                $this->parameterContainer = $parameters;
                $parameters               = null;
            } else {
                $this->parameterContainer = new ParameterContainer();
            }
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        if ($this->parameterContainer->count() > 0) {
            $this->bindParametersFromContainer();
        }
        /** END Standard ParameterContainer Merging Block */

        if ($this->profiler) {
            $this->profiler->profilerStart($this);
        }

        try {
            $this->resource->execute();
        } catch (PDOException $e) {
            if ($this->profiler) {
                $this->profiler->profilerFinish();
            }

            $code = $e->getCode();
            if (! is_int($code)) {
                $code = 0;
            }

            throw new Exception\InvalidQueryException(
                'Statement could not be executed (' . implode(' - ', $this->resource->errorInfo()) . ')',
                $code,
                $e
            );
        }

        if ($this->profiler) {
            $this->profiler->profilerFinish();
        }

        return $this->driver->createResult($this->resource, $this);
    }
}
