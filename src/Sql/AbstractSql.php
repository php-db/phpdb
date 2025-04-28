<?php

namespace Laminas\Db\Sql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Platform\Sql92 as DefaultAdapterPlatform;
use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;
use ValueError;

use function count;
use function current;
use function get_object_vars;
use function gettype;
use function implode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function join;
use function key;
use function preg_replace;
use function rtrim;
use function sprintf;
use function strtoupper;
use function vsprintf;

abstract class AbstractSql implements SqlInterface
{
    /**
     * Specifications for Sql String generation
     *
     * @var string[]|array[]
     */
    protected array $specifications = [];

    protected array $processInfo = ['paramPrefix' => '', 'subselectCount' => 0];

    protected array $instanceParameterIndex = [];

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $adapterPlatform = $adapterPlatform ?: new DefaultAdapterPlatform();

        return $this->buildSqlString($adapterPlatform);
    }

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $sqls       = [];
        $parameters = [];

        foreach ($this->specifications as $name => $specification) {
            $parameters[$name] = $this->{'process' . $name}(
                $platform,
                $driver,
                $parameterContainer,
                $sqls,
                $parameters
            );

            if ($specification && is_array($parameters[$name])) {
                $sqls[$name] = $this->createSqlFromSpecificationAndParameters($specification, $parameters[$name]);
                continue;
            }

            if (is_string($parameters[$name])) {
                $sqls[$name] = $parameters[$name];
            }
        }

        return rtrim(implode(' ', $sqls), "\n ,");
    }

    /**
     * Render table with alias in from/join parts
     *
     * @param string $table
     * @param string $alias
     * @return string
     * @todo move TableIdentifier concatenation here
     */
    protected function renderTable($table, $alias = null)
    {
        return $table . ($alias ? ' AS ' . $alias : '');
    }

    /**
     * @staticvar int $runtimeExpressionPrefix
     */
    protected function processExpression(
        ExpressionInterface $expression,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null
    ): string {
        // static counter for the number of times this method was invoked across the PHP runtime
        static $runtimeExpressionPrefix = 0;

        $namedParameterPrefix = $namedParameterPrefix === null || $namedParameterPrefix === ''
            ? ''
            : $this->processInfo['paramPrefix'] . $namedParameterPrefix;

        if ($parameterContainer && $namedParameterPrefix === '') {
            $namedParameterPrefix = sprintf('expr%04dParam', ++$runtimeExpressionPrefix);
        } else {
            $namedParameterPrefix = preg_replace('/\s/', '__', $namedParameterPrefix);
        }

        if (! isset($this->instanceParameterIndex[$namedParameterPrefix])) {
            $this->instanceParameterIndex[$namedParameterPrefix] = 1;
        }

        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];
        $expressionData       = $expression->getExpressionData();
        $sqlStrings           = [];

        foreach ($expressionData->getExpressionParts() as $expressionPart) {
            $specification    = $expressionPart->getSpecificationString(true);
            $expressionValues = $expressionPart->getSpecificationValues();
            $values           = [];
            foreach ($expressionValues as $vIndex => $argument) {
                $values[] = (string) $this->processExpressionValue(
                    $argument,
                    $expressionParamIndex,
                    $namedParameterPrefix,
                    $vIndex,
                    $platform,
                    $driver,
                    $parameterContainer,
                );
            }
            $sqlStrings[] = vsprintf($specification, $values);
        }

        return join(' ', $sqlStrings);
    }

    protected function processExpressionValue(
        Argument $argument,
        int &$expressionParamIndex,
        string $namedParameterPrefix,
        int $vIndex,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
        $argument->getValue();

        return match ($argument->getType()) {
            ArgumentType::Select => $this->processExpressionOrSelect(
                $argument,
                $namedParameterPrefix,
                $vIndex,
                $platform,
                $driver,
                $parameterContainer
            ),
            ArgumentType::Identifier => $platform->quoteIdentifierInFragment($argument->getValueAsString()),
            ArgumentType::Literal => $argument->getValueAsString(),
            ArgumentType::Value => $parameterContainer ?
                $this->processExpressionParameterName(
                    $argument->getValue(),
                    $namedParameterPrefix,
                    $expressionParamIndex,
                    $driver,
                    $parameterContainer
                ) :
                $platform->quoteValue($argument->getValueAsString())
        };
    }

    protected function processExpressionOrSelect(
        Argument $argument,
        string $namedParameterPrefix,
        int $vIndex,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $value = $argument->getValue();

        return match (true) {
            $value instanceof Select => '(' . $this->processSubSelect($value, $platform, $driver, $parameterContainer) . ')',
            $value instanceof ExpressionInterface => $this->processExpression(
                $value,
                $platform,
                $driver,
                $parameterContainer,
                "{$namedParameterPrefix}{$vIndex}subpart"
            ),
            default => throw new ValueError('Invalid Argument type'),
        };
    }

    protected function processExpressionParameterName(
        int|float|string|bool $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
        DriverInterface $driver,
        ParameterContainer $parameterContainer
    ): ?string {
        $name = $namedParameterPrefix . $expressionParamIndex++;
        $parameterContainer->offsetSet($name, $value);

        return $driver->formatParameterName($name);
    }

    /**
     * @param string|array $specifications
     * @param array        $parameters
     * @throws Exception\RuntimeException
     * @return string
     */
    protected function createSqlFromSpecificationAndParameters($specifications, $parameters)
    {
        if (is_string($specifications)) {
            return vsprintf($specifications, $parameters);
        }

        $parametersCount = count($parameters);

        foreach ($specifications as $specificationString => $paramSpecs) {
            if ($parametersCount === count($paramSpecs)) {
                break;
            }

            unset($specificationString, $paramSpecs);
        }

        if (! isset($specificationString)) {
            throw new Exception\RuntimeException(
                'A number of parameters was found that is not supported by this specification'
            );
        }

        $topParameters = [];
        foreach ($parameters as $position => $paramsForPosition) {
            if (isset($paramSpecs[$position]['combinedby'])) {
                $multiParamValues = [];
                foreach ($paramsForPosition as $multiParamsForPosition) {
                    if (is_array($multiParamsForPosition)) {
                        $ppCount = count($multiParamsForPosition);
                    } else {
                        $ppCount                = 1;
                        $multiParamsForPosition = [$multiParamsForPosition];
                    }

                    if (! isset($paramSpecs[$position][$ppCount])) {
                        throw new Exception\RuntimeException(sprintf(
                            'A number of parameters (%d) was found that is not supported by this specification',
                            $ppCount
                        ));
                    }
                    $multiParamValues[] = vsprintf($paramSpecs[$position][$ppCount], $multiParamsForPosition);
                }
                $topParameters[] = implode($paramSpecs[$position]['combinedby'], $multiParamValues);
            } elseif ($paramSpecs[$position] !== null) {
                $ppCount = count($paramsForPosition);
                if (! isset($paramSpecs[$position][$ppCount])) {
                    throw new Exception\RuntimeException(sprintf(
                        'A number of parameters (%d) was found that is not supported by this specification',
                        $ppCount
                    ));
                }
                $topParameters[] = vsprintf($paramSpecs[$position][$ppCount], $paramsForPosition);
            } else {
                $topParameters[] = $paramsForPosition;
            }
        }

        return vsprintf($specificationString, $topParameters);
    }

    /**
     * @return string
     */
    protected function processSubSelect(
        Select $subselect,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ) {
        if ($this instanceof PlatformDecoratorInterface) {
            $decorator = clone $this;
            $decorator->setSubject($subselect);
        } else {
            $decorator = $subselect;
        }

        if ($parameterContainer) {
            // Track subselect prefix and count for parameters
            $processInfoContext = $decorator instanceof PlatformDecoratorInterface ? $subselect : $decorator;
            $this->processInfo['subselectCount']++;
            $processInfoContext->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
            $processInfoContext->processInfo['paramPrefix']    = 'subselect'
                . $processInfoContext->processInfo['subselectCount'];

            $sql = $decorator->buildSqlString($platform, $driver, $parameterContainer);

            // copy count
            $this->processInfo['subselectCount'] = $decorator->processInfo['subselectCount'];

            return $sql;
        }

        return $decorator->buildSqlString($platform, $driver, $parameterContainer);
    }

    /**
     * @param Join                    $joins
     * @param PlatformInterface       $platform
     * @param DriverInterface|null    $driver
     * @param ParameterContainer|null $parameterContainer
     * @return null|string[][][] Null if no joins present, array of JOIN statements otherwise
     */
    protected function processJoin(
        Join $joins,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): array|null {
        if (! $joins->count()) {
            return null;
        }

        // process joins
        $joinSpecArgArray = [];
        foreach ($joins->getJoins() as $j => $join) {
            $joinAs   = null;

            // table name
            if (is_array($join['name'])) {
                $joinName = current($join['name']);
                $joinAs   = $platform->quoteIdentifier(key($join['name']));
            } else {
                $joinName = $join['name'];
            }

            if ($joinName instanceof Expression) {
                $joinName = $joinName->getExpression();
            } elseif ($joinName instanceof TableIdentifier) {
                $joinName = $joinName->getTableAndSchema();
                $joinName = ($joinName[1]
                        ? $platform->quoteIdentifier($joinName[1]) . $platform->getIdentifierSeparator()
                        : '') . $platform->quoteIdentifier($joinName[0]);
            } elseif ($joinName instanceof Select) {
                $joinName = '(' . $this->processSubSelect($joinName, $platform, $driver, $parameterContainer) . ')';
            } elseif (is_string($joinName) || (is_object($joinName) && is_callable([$joinName, '__toString']))) {
                $joinName = $platform->quoteIdentifier($joinName);
            } else {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Join name expected to be Expression|TableIdentifier|Select|string, "%s" given',
                    gettype($joinName)
                ));
            }

            $joinSpecArgArray[$j] = [
                strtoupper($join['type']),
                $this->renderTable($joinName, $joinAs),
            ];

            // on expression
            // note: for Expression objects, pass them to processExpression with a prefix specific to each join
            // (used for named parameters)
            if ($join['on'] instanceof ExpressionInterface) {
                $joinSpecArgArray[$j][] = $this->processExpression(
                    $join['on'],
                    $platform,
                    $driver,
                    $parameterContainer,
                    'join' . ($j + 1) . 'part'
                );
            } else {
                // on
                $joinSpecArgArray[$j][] = $platform->quoteIdentifierInFragment(
                    $join['on'],
                    ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>']
                );
            }
        }

        return [$joinSpecArgArray];
    }

    /**
     * @param null|array|ExpressionInterface|Select $column
     * @return string
     */
    protected function resolveColumnValue(
        $column,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null
    ) {
        $namedParameterPrefix = ! $namedParameterPrefix
            ? $namedParameterPrefix
            : $this->processInfo['paramPrefix'] . $namedParameterPrefix;
        $isIdentifier         = false;
        $fromTable            = '';
        if (is_array($column)) {
            if (isset($column['isIdentifier'])) {
                $isIdentifier = (bool) $column['isIdentifier'];
            }
            if (isset($column['fromTable']) && $column['fromTable'] !== null) {
                $fromTable = $column['fromTable'];
            }
            $column = $column['column'];
        }

        if ($column instanceof ExpressionInterface) {
            return $this->processExpression($column, $platform, $driver, $parameterContainer, $namedParameterPrefix);
        }
        if ($column instanceof Select) {
            return '(' . $this->processSubSelect($column, $platform, $driver, $parameterContainer) . ')';
        }
        if ($column === null) {
            return 'NULL';
        }

        return $isIdentifier
            ? $fromTable . $platform->quoteIdentifierInFragment($column)
            : $platform->quoteValue($column);
    }

    /**
     * @param string|TableIdentifier|Select $table
     * @return string
     */
    protected function resolveTable(
        $table,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ) {
        $schema = null;
        if ($table instanceof TableIdentifier) {
            [$table, $schema] = $table->getTableAndSchema();
        }

        if ($table instanceof Select) {
            $table = '(' . $this->processSubselect($table, $platform, $driver, $parameterContainer) . ')';
        } elseif ($table) {
            $table = $platform->quoteIdentifier($table);
        }

        if ($schema && $table) {
            $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
        }

        return $table;
    }

    /**
     * Copy variables from the subject into the local properties
     *
     * @return void
     */
    protected function localizeVariables()
    {
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
