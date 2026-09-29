<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

use function extension_loaded;
use function is_int;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;

abstract class AbstractPdo implements PdoDriverInterface, ProfilerAwareInterface
{
    protected (PdoConnectionInterface&AbstractConnection&PdoDriverAwareInterface)|PDO $connection;

    protected StatementInterface&PdoDriverAwareInterface $statementPrototype;

    protected ResultInterface $resultPrototype;

    /** @internal */
    protected ?ProfilerInterface $profiler;

    /**
     * Check environment
     */
    #[Override]
    public function checkEnvironment(): bool
    {
        if (! extension_loaded('PDO')) {
            // @codeCoverageIgnoreStart
            throw Exception\RuntimeException::forMissingPdoExtension();

            // @codeCoverageIgnoreEnd
        }
        return true;
    }

    /**
     * todo: this needs improved
     *
     * @param PDOStatement|string $sqlOrResource
     */
    #[Override]
    public function createStatement($sqlOrResource = null): StatementInterface
    {
        /** @var Statement $statement */
        $statement = clone $this->statementPrototype;
        if ($sqlOrResource instanceof PDOStatement) {
            $statement->setResource($sqlOrResource);
        } else {
            if (is_string($sqlOrResource)) {
                $statement->setSql($sqlOrResource);
            }
            if (! $this->connection->isConnected()) {
                $this->connection->connect();
            }
            /** @var PDO $resource */
            $resource = $this->connection->getResource();
            $statement->initialize($resource);
        }
        return $statement;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function formatParameterName(string|int $name, ?string $type = null): string
    {
        if (null === $type && ! is_numeric($name) || self::PARAMETERIZATION_NAMED === $type) {
            // proposed fix for passing $name as int with type self::PARAMETERIZATION_NAMED
            if (is_int($name) && self::PARAMETERIZATION_NAMED === $type) {
                $name = (string) $name;
            }
            // end proposed fix
            $name = ltrim($name, ':');
            // @see https://bugs.php.net/bug.php?id=43130
            if (preg_match('/[^a-zA-Z0-9_]/', $name)) {
                throw Exception\RuntimeException::forInvalidPdoParam($name);
            }
            return ":{$name}";
        }

        return '?';
    }

    #[Override]
    public function getConnection(): PdoConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return $this->connection->getLastGeneratedValue($name);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_NAMED;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * {@inheritDoc}
     */
    public function getResultPrototype(): ?ResultInterface
    {
        return $this->resultPrototype;
    }

    #[Override]
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        if ($this->connection instanceof ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }
}
