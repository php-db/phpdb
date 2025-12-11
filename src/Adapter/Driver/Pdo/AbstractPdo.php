<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
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
use function sprintf;

abstract class AbstractPdo implements PdoDriverInterface, ProfilerAwareInterface
{
    /** @internal */
    protected ?ProfilerInterface $profiler;

    public function __construct(
        protected AbstractPdoConnection|PDO $connection,
        protected StatementInterface&PdoDriverAwareInterface $statementPrototype,
        protected ResultInterface $resultPrototype,
        array $features = [],
    ) {
        if ($this->connection instanceof PdoDriverAwareInterface) {
            $this->connection->setDriver($this);
        }

        $this->statementPrototype->setDriver($this);

        // $features is not constructor promoted because $this->features is defined in the trait
        if ($features !== [] && $this instanceof DriverFeatureProviderInterface) {
            $this->addFeatures($features);
        }
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

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Check environment
     */
    #[Override]
    public function checkEnvironment(): bool
    {
        if (! extension_loaded('PDO')) {
            throw new Exception\RuntimeException(
                'The PDO extension is required for this adapter but the extension is not loaded'
            );
        }
        return true;
    }

    #[Override]
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
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
    public function getResultPrototype(): ?ResultInterface
    {
        return $this->resultPrototype;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_NAMED;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function formatParameterName(string|int $name, ?string $type = null): string
    {
        if ($type === null && ! is_numeric($name) || $type === self::PARAMETERIZATION_NAMED) {
            // proposed fix for passing $name as int with type self::PARAMETERIZATION_NAMED
            if (is_int($name) && $type === self::PARAMETERIZATION_NAMED) {
                $name = (string) $name;
            }
            // end proposed fix
            $name = ltrim($name, ':');
            // @see https://bugs.php.net/bug.php?id=43130
            if (preg_match('/[^a-zA-Z0-9_]/', $name)) {
                throw new Exception\RuntimeException(sprintf(
                    'The PDO param %s contains invalid characters.'
                    . ' Only alphabetic characters, digits, and underscores (_)'
                    . ' are allowed.',
                    $name
                ));
            }
            return ':' . $name;
        }

        return '?';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): int|string|bool|null
    {
        return $this->connection->getLastGeneratedValue($name);
    }
}
