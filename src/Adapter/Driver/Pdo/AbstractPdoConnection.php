<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Pdo;

use Laminas\Db\Adapter\Driver\AbstractConnection;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\PdoDriverAwareInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Override;
use PDO;

use function is_array;
use function str_replace;
use function strpos;
use function strtolower;
use function substr;

abstract class AbstractPdoConnection extends AbstractConnection implements PdoDriverAwareInterface
{
    protected ?PdoDriverInterface $driver = null;

    /** @var ?PDO $resource */
    protected $resource;

    /** @var string */
    protected ?string $dsn;

    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        array|PDO|null $connectionParameters = null
    ){
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);
        } elseif ($connectionParameters instanceof PDO) {
            $this->setResource($connectionParameters);
        } elseif (null !== $connectionParameters) {
            throw new Exception\InvalidArgumentException(
                '$connection must be an array of parameters, a \PDO object or null'
            );
        }
    }

    #[Override]
    public function setDriver(PdoDriverInterface $driver): PdoDriverAwareInterface
    {
        $this->driver = $driver;

        return $this;
    }

    #[Override]
    public function setConnectionParameters(array $connectionParameters): static
    {
        $this->connectionParameters = $connectionParameters;
        if (isset($connectionParameters['dsn'])) {
            $this->driverName = substr(
                $connectionParameters['dsn'],
                0,
                strpos($connectionParameters['dsn'], ':')
            );
        } elseif (isset($connectionParameters['pdodriver'])) {
            $this->driverName = strtolower($connectionParameters['pdodriver']);
        } elseif (isset($connectionParameters['driver'])) {
            $this->driverName = strtolower(substr(
                str_replace(['-', '_', ' '], '', $connectionParameters['driver']),
                3
            ));
        }

        return $this;
    }

    /**
     * Get the dsn string for this connection
     *
     * @throws RuntimeException
     */
    #[Override]
    public function getDsn(): string
    {
        if (! $this->dsn) {
            throw new Exception\RuntimeException(
                'The DSN has not been set or constructed from parameters in connect() for this Connection'
            );
        }

        return $this->dsn;
    }

    /**
     * @inheritDoc
     */
    // public function getCurrentSchema()
    // {
    //     if (! $this->isConnected()) {
    //         $this->connect();
    //     }

    //     switch ($this->driverName) {
    //         case 'mysql':
    //             $sql = 'SELECT DATABASE()';
    //             break;
    //         case 'sqlite':
    //             return 'main';
    //         case 'sqlsrv':
    //         case 'dblib':
    //             $sql = 'SELECT SCHEMA_NAME()';
    //             break;
    //         case 'pgsql':
    //         default:
    //             $sql = 'SELECT CURRENT_SCHEMA';
    //             break;
    //     }

    //     /** @var PDOStatement $result */
    //     $result = $this->resource->query($sql);
    //     if ($result instanceof PDOStatement) {
    //         return $result->fetchColumn();
    //     }

    //     return false;
    // }

    public function setResource(PDO $resource): static
    {
        $this->resource   = $resource;
        $this->driverName = strtolower($this->resource->getAttribute(PDO::ATTR_DRIVER_NAME));

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\InvalidConnectionParametersException
     * @throws Exception\RuntimeException
     */
    // public function connect()
    // {
    //     if ($this->resource) {
    //         return $this;
    //     }

    //     $dsn     = $username = $password = $hostname = $database = null;
    //     $options = [];
    //     foreach ($this->connectionParameters as $key => $value) {
    //         switch (strtolower($key)) {
    //             case 'dsn':
    //                 $dsn = $value;
    //                 break;
    //             case 'driver':
    //                 $value = strtolower((string) $value);
    //                 if (strpos($value, 'pdo') === 0) {
    //                     $pdoDriver = str_replace(['-', '_', ' '], '', $value);
    //                     $pdoDriver = substr($pdoDriver, 3) ?: '';
    //                 }
    //                 break;
    //             case 'pdodriver':
    //                 $pdoDriver = (string) $value;
    //                 break;
    //             case 'user':
    //             case 'username':
    //                 $username = (string) $value;
    //                 break;
    //             case 'pass':
    //             case 'password':
    //                 $password = (string) $value;
    //                 break;
    //             case 'host':
    //             case 'hostname':
    //                 $hostname = (string) $value;
    //                 break;
    //             case 'port':
    //                 $port = (int) $value;
    //                 break;
    //             case 'database':
    //             case 'dbname':
    //                 $database = (string) $value;
    //                 break;
    //             case 'charset':
    //                 $charset = (string) $value;
    //                 break;
    //             case 'unix_socket':
    //                 $unixSocket = (string) $value;
    //                 break;
    //             case 'version':
    //                 $version = (string) $value;
    //                 break;
    //             case 'driver_options':
    //             case 'options':
    //                 $value   = (array) $value;
    //                 $options = array_diff_key($options, $value) + $value;
    //                 break;
    //             default:
    //                 $options[$key] = $value;
    //                 break;
    //         }
    //     }

    //     if (isset($hostname) && isset($unixSocket)) {
    //         throw new Exception\InvalidConnectionParametersException(
    //             'Ambiguous connection parameters, both hostname and unix_socket parameters were set',
    //             $this->connectionParameters
    //         );
    //     }

    //     if (! isset($dsn) && isset($pdoDriver)) {
    //         $dsn = [];
    //         switch ($pdoDriver) {
    //             case 'sqlite':
    //                 $dsn[] = $database;
    //                 break;
    //             case 'sqlsrv':
    //                 if (isset($database)) {
    //                     $dsn[] = "database={$database}";
    //                 }
    //                 if (isset($hostname)) {
    //                     $dsn[] = "server={$hostname}";
    //                 }
    //                 break;
    //             default:
    //                 if (isset($database)) {
    //                     $dsn[] = "dbname={$database}";
    //                 }
    //                 if (isset($hostname)) {
    //                     $dsn[] = "host={$hostname}";
    //                 }
    //                 if (isset($port)) {
    //                     $dsn[] = "port={$port}";
    //                 }
    //                 if (isset($charset) && $pdoDriver !== 'pgsql') {
    //                     $dsn[] = "charset={$charset}";
    //                 }
    //                 if (isset($unixSocket)) {
    //                     $dsn[] = "unix_socket={$unixSocket}";
    //                 }
    //                 if (isset($version)) {
    //                     $dsn[] = "version={$version}";
    //                 }
    //                 break;
    //         }
    //         $dsn = $pdoDriver . ':' . implode(';', $dsn);
    //     } elseif (! isset($dsn)) {
    //         throw new Exception\InvalidConnectionParametersException(
    //             'A dsn was not provided or could not be constructed from your parameters',
    //             $this->connectionParameters
    //         );
    //     }

    //     $this->dsn = $dsn;

    //     try {
    //         $this->resource = new \PDO($dsn, $username, $password, $options);
    //         $this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    //         if (isset($charset) && $pdoDriver === 'pgsql') {
    //             $this->resource->exec('SET NAMES ' . $this->resource->quote($charset));
    //         }
    //         $this->driverName = strtolower($this->resource->getAttribute(\PDO::ATTR_DRIVER_NAME));
    //     } catch (PDOException $e) {
    //         $code = $e->getCode();
    //         if (! is_int($code)) {
    //             $code = 0;
    //         }
    //         throw new Exception\RuntimeException('Connect Error: ' . $e->getMessage(), $code, $e);
    //     }

    //     return $this;
    // }

    /** @inheritDoc */
    public function isConnected(): bool
    {
        return $this->resource instanceof PDO;
    }

    /** @inheritDoc */
    public function beginTransaction(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if (0 === $this->nestedTransactionsCount) {
            $this->resource->beginTransaction();
            $this->inTransaction = true;
        }

        $this->nestedTransactionsCount++;

        return $this;
    }

    /** @inheritDoc */
    public function commit(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if ($this->inTransaction) {
            $this->nestedTransactionsCount -= 1;
        }

        /*
         * This shouldn't check for being in a transaction since
         * after issuing a SET autocommit=0; we have to commit too.
         */
        if (0 === $this->nestedTransactionsCount) {
            $this->resource->commit();
            $this->inTransaction = false;
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\RuntimeException
     */
    public function rollback(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback');
        }

        if (! $this->inTransaction()) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback');
        }

        $this->resource->rollBack();

        $this->inTransaction           = false;
        $this->nestedTransactionsCount = 0;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\InvalidQueryException
     */
    public function execute($sql): ?ResultInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if ($this->profiler) {
            $this->profiler->profilerStart($sql);
        }

        $resultResource = $this->resource->query($sql);

        if ($this->profiler) {
            $this->profiler->profilerFinish($sql);
        }

        if ($resultResource === false) {
            $errorInfo = $this->resource->errorInfo();
            throw new Exception\InvalidQueryException($errorInfo[2]);
        }

        return $this->driver->createResult($resultResource, $sql);
    }

    /** Prepare a statement */
    public function prepare(?string $sql = null): StatementInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        return $this->driver->createStatement($sql);
    }

    /**
     * {@inheritDoc}
     *
     * @param  string            $name
     * @return string|null|false
     */
    // public function getLastGeneratedValue($name = null)
    // {
    //     if (
    //         $name === null
    //         && ($this->driverName === 'pgsql' || $this->driverName === 'firebird')
    //     ) {
    //         return;
    //     }

    //     try {
    //         return $this->resource->lastInsertId($name);
    //     } catch (\Exception $e) {
    //         // do nothing
    //     }

    //     return false;
    // }
}
