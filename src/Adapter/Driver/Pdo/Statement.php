<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Pdo;

use Laminas\Db\Adapter\Driver\PdoDriverAwareInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler\ProfilerAwareInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Adapter\StatementContainerInterface;
use PDO;
use PDOException;
use PDOStatement;

use function implode;
use function is_array;
use function is_bool;
use function is_int;

class Statement implements StatementInterface, PdoDriverAwareInterface, ProfilerAwareInterface
{
    protected PDO $pdo;

    protected ?ProfilerInterface $profiler = null;

    protected PdoDriverInterface $driver;

    protected string $sql = '';

    protected bool $isQuery;

    protected bool $parametersBound = false;

    protected PDOStatement|false|null $resource;

    protected bool $isPrepared = false;

    public function __construct(
        protected ParameterContainer $parameterContainer = new ParameterContainer(),
        protected array $options = [],
    ) {
    }

    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;
        return $this;
    }

    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        return $this;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /** Initialize */
    public function initialize(PDO $connectionResource): static
    {
        $this->pdo = $connectionResource;
        return $this;
    }

    /** Set resource */
    public function setResource(PDOStatement $pdoStatement): static
    {
        $this->resource = $pdoStatement;
        return $this;
    }

    /** Get resource */
    public function getResource(): ?PDOStatement
    {
        return $this->resource;
    }

    /** Set sql */
    public function setSql(?string $sql): StatementContainerInterface
    {
        $this->sql = $sql;
        return $this;
    }

    /** Get sql */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->parameterContainer;
    }

    /** @throws Exception\RuntimeException */
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

        $this->profiler?->profilerStart($this);

        try {
            $this->resource->execute();
        } catch (PDOException $e) {

            $this->profiler?->profilerFinish();

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

        $this->profiler?->profilerFinish();

        return $this->driver->createResult($this->resource, $this);
    }

    /** Bind parameters from container */
    protected function bindParametersFromContainer(): void
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

    /** Perform a deep clone */
    public function __clone(): void
    {
        $this->isPrepared      = false;
        $this->parametersBound = false;
        $this->resource        = null;
        if ($this->parameterContainer) {
            $this->parameterContainer = clone $this->parameterContainer;
        }
    }
}
