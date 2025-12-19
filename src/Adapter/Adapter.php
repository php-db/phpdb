<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use Exception as PhpException;
use Override;
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
        protected Driver\DriverInterface $driver,
        protected Platform\PlatformInterface $platform,
        protected ResultSet\ResultSetInterface $queryResultSetPrototype = new ResultSet\ResultSet(),
        protected ?Profiler\ProfilerInterface $profiler = null
    ) {
        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }

    #[Override]
    public function setProfiler(Profiler\ProfilerInterface $profiler): Profiler\ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        if ($this->driver instanceof Profiler\ProfilerAwareInterface) {
            $this->driver->setProfiler($profiler);
        }
        return $this;
    }

    #[Override]
    public function getDriver(): Driver\DriverInterface
    {
        return $this->driver;
    }

    #[Override]
    public function getPlatform(): Platform\PlatformInterface
    {
        return $this->platform;
    }

    #[Override]
    public function getProfiler(): ?Profiler\ProfilerInterface
    {
        return $this->profiler;
    }

    #[Override]
    public function getQueryResultSetPrototype(): ResultSet\ResultSetInterface
    {
        return $this->queryResultSetPrototype;
    }

    #[Override]
    public function getCurrentSchema(): string|false
    {
        return $this->driver->getConnection()->getCurrentSchema();
    }

    /**
     * query() is a convenience function
     *
     * @throws Exception\InvalidArgumentException
     * @throws PhpException
     */
    #[Override]
    public function query(
        string $sql,
        ParameterContainer|array|string $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null
    ): Driver\StatementInterface|ResultSet\ResultSetInterface|Driver\ResultInterface {
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
    #[Override]
    public function createStatement(
        ?string $initialSql = null,
        ParameterContainer|array $initialParameters = []
    ): Driver\StatementInterface {
        $statement = $this->driver->createStatement($initialSql);
        if (
            is_array($initialParameters)
        ) {
            $initialParameters = new ParameterContainer($initialParameters);
        }
        $statement->setParameterContainer($initialParameters);
        return $statement;
    }

    /**
     * {@inheritDoc}
     */
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
        return $functions;
    }

    /** @throws Exception\InvalidArgumentException */
    public function __get(string $name): Driver\DriverInterface|Platform\PlatformInterface
    {
        return match (strtolower($name)) {
            'driver'   => $this->driver,
            'platform' => $this->platform,
            default    => throw new Exception\InvalidArgumentException('Invalid magic property on adapter'),
        };
    }
}
