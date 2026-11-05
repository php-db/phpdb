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
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;

/**
 * @api
 */
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
     * @param resource|object|string|null $sqlOrResource A resource or object other than a PDOStatement
     *                                                   is ignored, as with null
     *
     * @throws Exception\RuntimeException When the connection is a bare PDO instance.
     */
    #[Override]
    public function createStatement($sqlOrResource = null): StatementInterface
    {
        /** @var Statement $statement */
        $statement = clone $this->statementPrototype;
        if ($sqlOrResource instanceof PDOStatement) {
            return $statement->setResource($sqlOrResource);
        }

        if (is_string($sqlOrResource)) {
            $statement->setSql($sqlOrResource);
        }

        $connection = $this->getPdoConnection();
        if (! $connection->isConnected()) {
            $connection->connect();
        }

        /** @var PDO $resource */
        $resource = $connection->getResource();

        return $statement->initialize($resource);
    }

    /**
     * {@inheritDoc}
     *
     * An integer name is named only when the type asks for it; with no type, a numeric name is
     * positional. PDO rejects named parameters outside [a-zA-Z0-9_], see
     * https://bugs.php.net/bug.php?id=43130.
     *
     * The analyzer reads $name as the string that DriverInterface declares, so it reports the
     * cast that an integer name needs as redundant.
     *
     * @throws Exception\RuntimeException When a named parameter contains invalid characters.
     *
     * @mago-expect analysis:redundant-cast
     */
    #[Override]
    public function formatParameterName(string|int $name, ?string $type = null): string
    {
        $isPositional = null === $type ? is_numeric($name) : self::PARAMETERIZATION_NAMED !== $type;
        if ($isPositional) {
            return '?';
        }

        $name = ltrim((string) $name, characters: ':');
        if (1 === preg_match('/[^a-zA-Z0-9_]/', $name)) {
            throw Exception\RuntimeException::forInvalidPdoParam($name);
        }

        return ":{$name}";
    }

    /** @throws Exception\RuntimeException When the connection is a bare PDO instance. */
    #[Override]
    public function getConnection(): PdoConnectionInterface
    {
        return $this->getPdoConnection();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When the connection is a bare PDO instance.
     */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return $this->getPdoConnection()->getLastGeneratedValue($name);
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

    /**
     * The connection property also admits a bare PDO instance, which the driver methods
     * cannot use: it has no isConnected(), getResource() or getLastGeneratedValue().
     *
     * @throws Exception\RuntimeException When the connection is a bare PDO instance.
     */
    private function getPdoConnection(): PdoConnectionInterface&AbstractConnection&PdoDriverAwareInterface
    {
        return $this->connection instanceof PDO
            ? throw Exception\RuntimeException::forBarePdoConnection()
            : $this->connection;
    }
}
