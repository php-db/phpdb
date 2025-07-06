<?php

namespace PhpDb\Adapter;

use Exception as PhpException;
use PhpDb\ResultSet;

use function func_get_args;
use function in_array;
use function is_array;
use function is_string;
use function strtolower;

class Adapter implements AdapterInterface, Profiler\ProfilerAwareInterface, SchemaAwareInterface
{
    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        protected readonly Driver\DriverInterface $driver,
        protected readonly Platform\PlatformInterface $platform,
        protected readonly ResultSet\ResultSetInterface $queryResultSetPrototype,
        protected ?Profiler\ProfilerInterface $profiler = null
    ) {
        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }

    public function setProfiler(Profiler\ProfilerInterface $profiler): Profiler\ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        if ($this->driver instanceof Profiler\ProfilerAwareInterface) {
            $this->driver->setProfiler($profiler);
        }
        return $this;
    }

    public function getProfiler(): ?Profiler\ProfilerInterface
    {
        return $this->profiler;
    }

    public function getDriver(): Driver\DriverInterface
    {
        return $this->driver;
    }

    public function getPlatform(): Platform\PlatformInterface
    {
        return $this->platform;
    }

    public function getQueryResultSetPrototype(): ResultSet\ResultSetInterface
    {
        return $this->queryResultSetPrototype;
    }

    public function getCurrentSchema(): string
    {
        return $this->driver->getConnection()->getCurrentSchema();
    }

    /**
     * query() is a convenience function
     *
     * @throws Exception\InvalidArgumentException
     * @throws PhpException
     */
    public function query(
        string $sql,
        ParameterContainer|array|string $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null
    ): Driver\StatementInterface|ResultSet\ResultSet|Driver\ResultInterface {
        if (
            is_string($parametersOrQueryMode)
            && in_array($parametersOrQueryMode, [self::QUERY_MODE_PREPARE, self::QUERY_MODE_EXECUTE])
        ) {
            $mode       = $parametersOrQueryMode;
            $parameters = null;
        } elseif (is_array($parametersOrQueryMode) || $parametersOrQueryMode instanceof ParameterContainer) {
            $mode       = self::QUERY_MODE_PREPARE;
            $parameters = $parametersOrQueryMode;
        } else {
            throw new Exception\InvalidArgumentException(
                'Parameter 2 to this method must be a flag, an array, or ParameterContainer'
            );
        }

        if ($mode === self::QUERY_MODE_PREPARE) {
            $lastPreparedStatement = $this->driver->createStatement($sql);
            $lastPreparedStatement->prepare();
            if (is_array($parameters) || $parameters instanceof ParameterContainer) {
                if (is_array($parameters)) {
                    $lastPreparedStatement->setParameterContainer(new ParameterContainer($parameters));
                } else {
                    $lastPreparedStatement->setParameterContainer($parameters);
                }
                $result = $lastPreparedStatement->execute();
            } else {
                return $lastPreparedStatement;
            }
        } else {
            $result = $this->driver->getConnection()->execute($sql);
        }

        if ($result instanceof Driver\ResultInterface && $result->isQueryResult()) {
            $resultSet     = $resultPrototype ?? $this->queryResultSetPrototype;
            $resultSetCopy = clone $resultSet;

            $resultSetCopy->initialize($result);

            return $resultSetCopy;
        }

        return $result;
    }

    /**
     * Create statement
     */
    public function createStatement(
        ?string $initialSql = null,
        ParameterContainer|array|null $initialParameters = null
    ): Driver\StatementInterface {
        $statement = $this->driver->createStatement($initialSql);
        if (
            $initialParameters === null
            || ! $initialParameters instanceof ParameterContainer
            && is_array($initialParameters)
        ) {
            $initialParameters = new ParameterContainer(is_array($initialParameters) ? $initialParameters : []);
        }
        $statement->setParameterContainer($initialParameters);
        return $statement;
    }

    public function getHelpers()
    {
        $functions = [];
        $platform  = $this->platform;
        foreach (func_get_args() as $arg) {
            switch ($arg) {
                case self::FUNCTION_QUOTE_IDENTIFIER:
                    $functions[] = function ($value) use ($platform) {
                        return $platform->quoteIdentifier($value);
                    };
                    break;
                case self::FUNCTION_QUOTE_VALUE:
                    $functions[] = function ($value) use ($platform) {
                        return $platform->quoteValue($value);
                    };
                    break;
            }
        }
    }

    /**
     * @throws Exception\InvalidArgumentException
     * @return Driver\DriverInterface|Platform\PlatformInterface
     */
    public function __get(string $name)
    {
        return match (strtolower($name)) {
            'driver'   => $this->driver,
            'platform' => $this->platform,
            default    => throw new Exception\InvalidArgumentException('Invalid magic property on adapter'),
        };
    }

    // protected function createDriver(array $parameters): Driver\DriverInterface
    // {
    //     if (! isset($parameters['driver'])) {
    //         throw new Exception\InvalidArgumentException(
    //             __FUNCTION__ . ' expects a "driver" key to be present inside the parameters'
    //         );
    //     }

    //     if ($parameters['driver'] instanceof Driver\DriverInterface) {
    //         return $parameters['driver'];
    //     }

    //     if (! is_string($parameters['driver'])) {
    //         throw new Exception\InvalidArgumentException(
    //             __FUNCTION__ . ' expects a "driver" to be a string or instance of DriverInterface'
    //         );
    //     }

    //     $options = [];
    //     if (isset($parameters['options'])) {
    //         $options = (array) $parameters['options'];
    //         unset($parameters['options']);
    //     }

    //     $driverName = strtolower($parameters['driver']);
    //     switch ($driverName) {
    //         case 'mysqli':
    //             $driver = new Driver\Mysqli\Mysqli($parameters, null, null, $options);
    //             break;
    //         case 'sqlsrv':
    //             $driver = new Driver\Sqlsrv\Sqlsrv($parameters);
    //             break;
    //         case 'oci8':
    //             $driver = new Driver\Oci8\Oci8($parameters);
    //             break;
    //         case 'pgsql':
    //             $driver = new Driver\Pgsql\Pgsql($parameters);
    //             break;
    //         case 'ibmdb2':
    //             $driver = new Driver\IbmDb2\IbmDb2($parameters);
    //             break;
    //         case 'pdo':
    //         default:
    //             if ($driverName === 'pdo' || str_starts_with($driverName, 'pdo')) {
    //                 $driver = new Driver\Pdo\Pdo($parameters);
    //             }
    //     }

    //     if (! isset($driver) || ! $driver instanceof Driver\DriverInterface) {
    //         throw new Exception\InvalidArgumentException('DriverInterface expected');
    //     }

    //     return $driver;
    // }

    // protected function createPlatform(array $parameters): Platform\PlatformInterface
    // {
    //     if (isset($parameters['platform'])) {
    //         $platformName = $parameters['platform'];
    //     } elseif ($this->driver instanceof Driver\DriverInterface) {
    //         $platformName = $this->driver->getDatabasePlatformName();
    //     } else {
    //         throw new Exception\InvalidArgumentException(
    //             'A platform could not be determined from the provided configuration'
    //         );
    //     }

    //     // currently only supported by the IbmDb2 & Oracle concrete implementations
    //     $options = $parameters['platform_options'] ?? [];

    //     switch ($platformName) {
    //         case 'Mysql':
    //             // mysqli or pdo_mysql driver
    //             if ($this->driver instanceof Driver\Mysqli\Mysqli || $this->driver instanceof Driver\Pdo\Pdo) {
    //                 $driver = $this->driver;
    //             } else {
    //                 $driver = null;
    //             }
    //             return new Platform\Mysql($driver);
    //         case 'SqlServer':
    //             // PDO is only supported driver for quoting values in this platform
    //             return new Platform\SqlServer($this->driver instanceof Driver\Pdo\Pdo ? $this->driver : null);
    //         case 'Oracle':
    //             if ($this->driver instanceof Driver\Oci8\Oci8 || $this->driver instanceof Driver\Pdo\Pdo) {
    //                 $driver = $this->driver;
    //             } else {
    //                 $driver = null;
    //             }
    //             return new Platform\Oracle($options, $driver);
    //         case 'Sqlite':
    //             // PDO is only supported driver for quoting values in this platform
    //             if ($this->driver instanceof Driver\Pdo\Pdo) {
    //                 return new Platform\Sqlite($this->driver);
    //             }
    //             return new Platform\Sqlite(null);
    //         case 'Postgresql':
    //             // pgsql or pdo postgres driver
    //             if ($this->driver instanceof Driver\Pgsql\Pgsql || $this->driver instanceof Driver\Pdo\Pdo) {
    //                 $driver = $this->driver;
    //             } else {
    //                 $driver = null;
    //             }
    //             return new Platform\Postgresql($driver);
    //         case 'IbmDb2':
    //             // ibm_db2 driver escaping does not need an action connection
    //             return new Platform\IbmDb2($options);
    //         default:
    //             return new Platform\Sql92();
    //     }
    // }

    // protected function createProfiler(array $parameters): ?Profiler\ProfilerInterface
    // {
    //     if ($parameters['profiler'] instanceof Profiler\ProfilerInterface) {
    //         return $parameters['profiler'];
    //     }

    //     if (is_bool($parameters['profiler'])) {
    //         return $parameters['profiler'] === true ? new Profiler\Profiler() : null;
    //     }

    //     throw new Exception\InvalidArgumentException(
    //         '"profiler" parameter must be an instance of ProfilerInterface or a boolean'
    //     );
    // }
}
