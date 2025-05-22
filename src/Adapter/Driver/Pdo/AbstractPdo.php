<?php

namespace Laminas\Db\Adapter\Driver\Pdo;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\Feature\AbstractFeature;
use Laminas\Db\Adapter\Driver\Feature\DriverFeatureInterface;
use Laminas\Db\Adapter\Driver\PdoDriverAwareInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\Profiler;
use PDOStatement;

use function extension_loaded;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;
use function sprintf;

abstract class AbstractPdo implements PdoDriverInterface, DriverFeatureInterface, Profiler\ProfilerAwareInterface
{
    public const FEATURES_DEFAULT = 'default';

    protected ConnectionInterface $connection;

    protected StatementInterface&PdoDriverAwareInterface $statementPrototype;

    protected ResultInterface $resultPrototype;

    protected array $features = [];

    /** @internal */
    public Profiler\ProfilerInterface $profiler;

    public function setProfiler(Profiler\ProfilerInterface $profiler): static
    {
        $this->profiler = $profiler;
        if ($this->connection instanceof Profiler\ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof Profiler\ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }

    public function getProfiler(): ?Profiler\ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Register statement prototype
     */
    public function registerStatementPrototype(Statement $statementPrototype)
    {
        $this->statementPrototype = $statementPrototype;
        $this->statementPrototype->setDriver($this);
    }

    /**
     * Register result prototype
     */
    public function registerResultPrototype(Result $resultPrototype)
    {
        $this->resultPrototype = $resultPrototype;
    }

    /**
     * Add feature
     *
     * @param string $name
     * @param AbstractFeature $feature
     * @return $this Provides a fluent interface
     */
    public function addFeature($name, $feature)
    {
        if ($feature instanceof AbstractFeature) {
            $name = $feature->getName(); // overwrite the name, just in case
            $feature->setDriver($this);
        }
        $this->features[$name] = $feature;
        return $this;
    }

    /**
     * Setup the default features for Pdo
     *
     * @return $this Provides a fluent interface
     */
    // public function setupDefaultFeatures()
    // {
    //     $driverName = $this->connection->getDriverName();
    //     if ($driverName === 'sqlite') {
    //         $this->addFeature(null, new Feature\SqliteRowCounter());
    //         return $this;
    //     }

    //     if ($driverName === 'oci') {
    //         $this->addFeature(null, new Feature\OracleRowCounter());
    //         return $this;
    //     }

    //     return $this;
    // }

    /**
     * Get feature
     *
     * @param string $name
     * @return AbstractFeature|false
     */
    public function getFeature($name)
    {
        if (isset($this->features[$name])) {
            return $this->features[$name];
        }
        return false;
    }

    /**
     * Get database platform name
     *
     * @param  string $nameFormat
     * @return string
     */
    // public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
    // {
    //     $name = $this->getConnection()->getDriverName();
    //     if ($nameFormat === self::NAME_FORMAT_CAMELCASE) {
    //         switch ($name) {
    //             case 'pgsql':
    //                 return 'Postgresql';
    //             case 'oci':
    //                 return 'Oracle';
    //             case 'dblib':
    //             case 'sqlsrv':
    //                 return 'SqlServer';
    //             default:
    //                 return ucfirst($name);
    //         }
    //     } else {
    //         switch ($name) {
    //             case 'sqlite':
    //                 return 'SQLite';
    //             case 'mysql':
    //                 return 'MySQL';
    //             case 'pgsql':
    //                 return 'PostgreSQL';
    //             case 'oci':
    //                 return 'Oracle';
    //             case 'dblib':
    //             case 'sqlsrv':
    //                 return 'SQLServer';
    //             default:
    //                 return ucfirst($name);
    //         }
    //     }
    // }

    /**
     * Check environment
     */
    public function checkEnvironment(): bool
    {
        if (! extension_loaded('PDO')) {
            throw new Exception\RuntimeException(
                'The PDO extension is required for this adapter but the extension is not loaded'
            );
        }
        return true;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @param string|PDOStatement $sqlOrResource
     */
    public function createStatement($sqlOrResource = null): StatementInterface
    {
        /** @var Statement */
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
            /** @var \PDO */
            $resource = $this->connection->getResource();
            $statement->initialize($resource);
        }
        return $statement;
    }

    /**
     * @param resource $resource
     * @param mixed $context
     * @return Result
     */
    // public function createResult($resource, $context = null): ResultInterface
    // {
    //     $result   = clone $this->resultPrototype;
    //     $rowCount = null;

    //     // special feature, sqlite PDO counter
    //     if (
    //         $this->connection->getDriverName() === 'sqlite'
    //         && ($sqliteRowCounter = $this->getFeature('SqliteRowCounter'))
    //         && $resource->columnCount() > 0
    //     ) {
    //         $rowCount = $sqliteRowCounter->getRowCountClosure($context);
    //     }

    //     // special feature, oracle PDO counter
    //     if (
    //         $this->connection->getDriverName() === 'oci'
    //         && ($oracleRowCounter = $this->getFeature('OracleRowCounter'))
    //         && $resource->columnCount() > 0
    //     ) {
    //         $rowCount = $oracleRowCounter->getRowCountClosure($context);
    //     }

    //     $result->initialize($resource, $this->connection->getLastGeneratedValue(), $rowCount);
    //     return $result;
    // }

    /**
     * @return Result
     */
    public function getResultPrototype()
    {
        return $this->resultPrototype;
    }

    /**
     * @return string
     */
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_NAMED;
    }

    public function formatParameterName(string $name, ?string $type = null): string
    {
        if ($type === null && ! is_numeric($name) || $type === self::PARAMETERIZATION_NAMED) {
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

    public function getLastGeneratedValue(?string $name = null): int|string|null|false
    {
        return $this->connection->getLastGeneratedValue($name);
    }
}
