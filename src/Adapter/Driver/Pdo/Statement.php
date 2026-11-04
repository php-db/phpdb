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
use function is_bool;
use function is_int;
use function ltrim;
use function preg_match;

/**
 * @api
 */
class Statement implements StatementInterface, PdoDriverAwareInterface, ProfilerAwareInterface
{
    protected PDO $pdo;

    protected ?ProfilerInterface $profiler = null;

    protected PdoDriverInterface $driver;

    protected string $sql = '';

    protected bool $isQuery = false;

    protected bool $parametersBound = false;

    protected PDOStatement|false|null $resource = null;

    protected bool $isPrepared = false;

    /**
     * @param array<array-key, mixed> $options
     */
    public function __construct(
        protected ParameterContainer $parameterContainer = new ParameterContainer(),
        protected array $options = [],
    ) {}

    /**
     * @param ParameterContainer|array<array-key, mixed>|null $parameters
     *
     * @throws Exception\InvalidQueryException
     * @throws Exception\RuntimeException
     * @throws PDOException When the driver raises one while preparing or binding.
     */
    #[Override]
    public function execute(array|ParameterContainer|null $parameters = null): ?ResultInterface
    {
        if (! $this->isPrepared) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if ($parameters instanceof ParameterContainer) {
            $this->parameterContainer = $parameters;
            $parameters               = null;
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        if ($this->parameterContainer->count() > 0) {
            $this->bindParametersFromContainer();
        }
        /** END Standard ParameterContainer Merging Block */

        $resource = $this->getPreparedResource();

        $this->profiler?->profilerStart($this);

        try {
            $resource->execute();
        } catch (PDOException $e) {
            $this->profiler?->profilerFinish();

            $code = $e->getCode();
            if (! is_int($code)) {
                $code = 0;
            }

            throw Exception\InvalidQueryException::forFailedStatement(
                implode(' - ', $resource->errorInfo()),
                $code,
                $e,
            );
        }

        $this->profiler?->profilerFinish();

        return $this->driver->createResult($resource);
    }

    #[Override]
    public function getParameterContainer(): ParameterContainer
    {
        return $this->parameterContainer;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /** Get resource */
    #[Override]
    public function getResource(): PDOStatement|false|null
    {
        return $this->resource;
    }

    /** Get sql */
    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /** Initialize */
    public function initialize(PDO $connectionResource): StatementInterface
    {
        $this->pdo = $connectionResource;
        return $this;
    }

    #[Override]
    public function isPrepared(): bool
    {
        return $this->isPrepared;
    }

    /**
     * @throws Exception\RuntimeException
     * @throws PDOException When the driver raises one while preparing.
     */
    #[Override]
    public function prepare(?string $sql = null): StatementInterface
    {
        if ($this->isPrepared) {
            throw Exception\RuntimeException::forAlreadyPrepared();
        }

        if (null === $sql) {
            $sql = $this->sql;
        }

        $this->resource = $this->pdo->prepare($sql);

        if (false === $this->resource) {
            $error = $this->pdo->errorInfo();
            throw Exception\RuntimeException::forDriverError($error[2]);
        }

        $this->isPrepared = true;

        return $this;
    }

    #[Override]
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;
        return $this;
    }

    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    #[Override]
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        return $this;
    }

    /** Set resource */
    public function setResource(PDOStatement $pdoStatement): StatementInterface
    {
        $this->resource = $pdoStatement;
        return $this;
    }

    /** Set sql; a null SQL string is stored as an empty one */
    #[Override]
    public function setSql(?string $sql): StatementContainerInterface
    {
        $this->sql = $sql ?? '';
        return $this;
    }

    /**
     * Bind parameters from container
     *
     * Parameter values are bound by reference and can be of any type, so the loop and
     * PDOStatement::bindParam() both assign mixed values.
     *
     * @throws Exception\RuntimeException
     * @throws PDOException When the driver rejects a binding.
     *
     * @mago-expect analysis:mixed-assignment
     */
    protected function bindParametersFromContainer(): void
    {
        $resource   = $this->getPreparedResource();
        $parameters = $this->parameterContainer->getNamedArray();
        $errata     = $this->parameterContainer->getErrataIterator()->getArrayCopy();

        foreach ($parameters as $name => &$value) {
            $resource->bindParam(
                $this->toPdoParameter($name),
                $value,
                $this->toPdoType($errata[$name] ?? null, $value),
            );
        }

        $this->parametersBound = true;
    }

    /** @throws Exception\RuntimeException */
    private function getPreparedResource(): PDOStatement
    {
        return $this->resource instanceof PDOStatement
            ? $this->resource
            : throw Exception\RuntimeException::forUnpreparedStatement();
    }

    /** @throws Exception\RuntimeException When a named parameter contains invalid characters. */
    private function toPdoParameter(int|string $name): int|string
    {
        if (is_int($name)) {
            return $name + 1;
        }

        if (1 !== preg_match('/^:?[a-zA-Z0-9_]+$/', $name)) {
            throw Exception\RuntimeException::forInvalidPdoParam($name);
        }

        return ':' . ltrim($name, characters: ':');
    }

    private function toPdoType(mixed $errata, mixed $value): int
    {
        if (null !== $errata) {
            return match ($errata) {
                ParameterContainer::TYPE_INTEGER => PDO::PARAM_INT,
                ParameterContainer::TYPE_NULL    => PDO::PARAM_NULL,
                ParameterContainer::TYPE_LOB     => PDO::PARAM_LOB,
                default                          => PDO::PARAM_STR,
            };
        }

        return match (true) {
            is_int($value)  => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            null === $value => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    /** Perform a deep clone */
    public function __clone(): void
    {
        $this->isPrepared         = false;
        $this->parametersBound    = false;
        $this->resource           = null;
        $this->parameterContainer = clone $this->parameterContainer;
    }
}
