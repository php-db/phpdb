<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Adapter\StatementContainerInterface;

use function implode;
use function is_array;
use function is_int;
use function ltrim;

class Statement implements StatementInterface, PdoDriverAwareInterface, ProfilerAwareInterface
{
    protected PDO $pdo;

    protected ?ProfilerInterface $profiler = null;

    protected PdoDriverInterface $driver;

    protected string $sql = '';

    protected bool $isQuery;

    protected bool $parametersBound = false;

    protected PDOStatement|false|null $resource = null;

    protected bool $isPrepared = false;

    public function __construct(
        protected ?ParameterContainer $parameterContainer = null,
        protected array $options = [],
    ) {
    }

    #[Override]
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;
        return $this;
    }

    #[Override]
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
    public function initialize(PDO $connectionResource): StatementInterface
    {
        $this->pdo = $connectionResource;
        return $this;
    }

    /** Set resource */
    public function setResource(PDOStatement $pdoStatement): StatementInterface
    {
        $this->resource = $pdoStatement;
        return $this;
    }

    /** Get resource */
    #[Override]
    public function getResource(): PDOStatement|false|null
    {
        return $this->resource;
    }

    /** Set sql */
    #[Override]
    public function setSql(?string $sql): StatementContainerInterface
    {
        $this->sql = $sql;
        return $this;
    }

    /** Get sql */
    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    #[Override]
    public function getParameterContainer(): ParameterContainer
    {
        return $this->parameterContainer ??= new ParameterContainer();
    }

    /** @throws Exception\RuntimeException */
    #[Override]
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

    #[Override]
    public function isPrepared(): bool
    {
        return $this->isPrepared;
    }

    /** @throws Exception\InvalidQueryException */
    #[Override]
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

        return $this->driver->createResult($this->resource);
    }

    /** Bind parameters from container */
    protected function bindParametersFromContainer(): void
    {
        if ($this->parametersBound) {
            return;
        }

        $parameters = $this->parameterContainer->getNamedArray();
        $errata     = $this->parameterContainer->getErrataIterator()->getArrayCopy();

        foreach ($parameters as $name => &$value) {
            if (isset($errata[$name])) {
                $type = match ($errata[$name]) {
                    ParameterContainer::TYPE_INTEGER => PDO::PARAM_INT,
                    ParameterContainer::TYPE_NULL => PDO::PARAM_NULL,
                    ParameterContainer::TYPE_LOB => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR,
                };
            } else {
                $type = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    $value === null => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
            }

            // parameter is named or positional, value is reference
            $parameter = is_int($name) ? $name + 1 : ':' . ltrim($name, ':');
            $this->resource->bindParam($parameter, $value, $type);
        }

        $this->parametersBound = true;
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
