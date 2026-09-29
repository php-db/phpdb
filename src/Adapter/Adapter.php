<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use Exception as PhpException;
use Override;
use PhpDb\ResultSet;

use function func_get_args;
use function is_array;
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
        protected ?Profiler\ProfilerInterface $profiler = null,
    ) {
        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }

    /**
     * Create statement
     */
    #[Override]
    public function createStatement(
        ?string $initialSql = null,
        ParameterContainer|array $initialParameters = [],
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
     * Execute raw SQL or a prepared statement.
     *
     * Narrows the driver's execution result to a Driver\ResultInterface,
     * never a wrapped ResultSet. Callers can check isQueryResult() and use
     * getQueryResult() themselves if they want the result wrapped.
     *
     * @throws Exception\RuntimeException When execution did not produce a result.
     */
    #[Override]
    public function executeQuery(Driver\StatementInterface|string $sql): Driver\ResultInterface
    {
        $result = $sql instanceof Driver\StatementInterface
            ? $sql->execute()
            : $this->driver->getConnection()->execute($sql);

        if (! $result instanceof Driver\ResultInterface) {
            throw Exception\RuntimeException::forMissingQueryResult();
        }

        return $result;
    }

    #[Override]
    public function getCurrentSchema(): string|false
    {
        return $this->driver->getConnection()->getCurrentSchema();
    }

    #[Override]
    public function getDriver(): Driver\DriverInterface
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getHelpers()
    {
        $functions = [];
        $platform  = $this->platform;
        foreach (func_get_args() as $arg) {
            switch ($arg) {
                case self::FUNCTION_QUOTE_IDENTIFIER:
                    $functions[] = static function ($value) use ($platform) {
                        return $platform->quoteIdentifier($value);
                    };
                    break;
                case self::FUNCTION_QUOTE_VALUE:
                    $functions[] = static function ($value) use ($platform) {
                        return $platform->quoteValue($value);
                    };
                    break;
            }
        }
        return $functions;
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

    /**
     * Prepare a statement for the given SQL, optionally binding parameters.
     *
     * Always prepares the statement; never executes it. Use executeQuery()
     * to run the returned statement.
     */
    #[Override]
    public function prepareQuery(
        string $sql,
        ParameterContainer|array $parameters = [],
    ): Driver\StatementInterface {
        $statement = $this->driver->createStatement($sql);

        if (is_array($parameters)) {
            $parameters = new ParameterContainer($parameters);
        }

        $statement->setParameterContainer($parameters);
        $statement->prepare();

        return $statement;
    }

    /**
     * query() is a convenience function
     *
     * @deprecated Use prepareQuery() and executeQuery() instead. query() will be removed in a future version.
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException When execution did not produce a result.
     * @throws PhpException
     */
    #[Override]
    public function query(
        string $sql,
        ParameterContainer|array|string $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null,
    ): Driver\StatementInterface|ResultSet\ResultSetInterface|Driver\ResultInterface {
        if (self::QUERY_MODE_PREPARE === $parametersOrQueryMode) {
            return $this->prepareQuery($sql);
        }

        $sql = match (true) {
            self::QUERY_MODE_EXECUTE === $parametersOrQueryMode => $sql,
            $parametersOrQueryMode instanceof ParameterContainer,
            is_array($parametersOrQueryMode),
                => $this->prepareQuery($sql, $parametersOrQueryMode),
            default => throw Exception\InvalidArgumentException::forIncorrectFlag(),
        };

        $result = $this->executeQuery($sql);

        return $result->isQueryResult()
            ? $result->getQueryResult($resultPrototype ?? $this->queryResultSetPrototype)
            : $result;
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

    /** @throws Exception\InvalidArgumentException */
    public function __get(string $name): Driver\DriverInterface|Platform\PlatformInterface
    {
        return match (strtolower($name)) {
            'driver'   => $this->driver,
            'platform' => $this->platform,
            default    => throw Exception\InvalidArgumentException::forInvalidMagicProperty(),
        };
    }
}
