<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92 as DefaultAdapterPlatform;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function count;
use function current;
use function get_object_vars;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function key;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtoupper;
use function strtr;
use function vsprintf;

abstract class AbstractSql implements SqlInterface
{
    /** Pre-computed regex pattern for value marker replacement */
    private const VALUE_PATTERN = '/\\{\\?\\}/';

    /** Pre-computed regex pattern for select marker replacement */
    private const SELECT_PATTERN = '/\\{SQL\\}/';

    public ?object $subject = null;

    protected array $processInfo = ['paramPrefix' => '', 'subselectCount' => 0];

    protected array $instanceParameterIndex = [];

    /**
     * Specifications for Sql String generation (used by DDL classes)
     *
     * @var string[]|array[]
     */
    protected array $specifications = [];

    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        return $this->buildSqlString($adapterPlatform ?? new DefaultAdapterPlatform());
    }

    /**
     * Build the SQL string. Override in subclasses for optimized implementations.
     */
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $sql = '';
        foreach ($this->specifications as $name => $specification) {
            $result = $this->{'process' . $name}($platform, $driver, $parameterContainer);

            if (is_array($result)) {
                $part = $this->createSqlFromSpecificationAndParameters($specification, $result);
                $sql .= $sql === '' ? $part : ' ' . $part;
            } elseif ($result !== null) {
                $sql .= $sql === '' ? $result : ' ' . $result;
            }
        }

        return rtrim($sql, "\n ,");
    }

    /**
     * Create SQL from specification and parameters (used by DDL classes).
     *
     * @throws Exception\RuntimeException
     */
    protected function createSqlFromSpecificationAndParameters(array|string $specifications, array $parameters): string
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

        if (!isset($specificationString)) {
            throw new Exception\RuntimeException('A number of parameters was found that is not supported by this specification');
        }

        $topParameters = [];
        foreach ($parameters as $position => $paramsForPosition) {
            if (isset($paramSpecs[$position]['combinedby'])) {
                $multiParamValues = [];
                foreach ($paramsForPosition as $multiParamsForPosition) {
                    $ppCount = is_array($multiParamsForPosition) ? count($multiParamsForPosition) : 1;
                    if (!is_array($multiParamsForPosition)) {
                        $multiParamsForPosition = [$multiParamsForPosition];
                    }
                    if (!isset($paramSpecs[$position][$ppCount])) {
                        throw new Exception\RuntimeException(sprintf('A number of parameters (%d) was found that is not supported by this specification', $ppCount));
                    }
                    $multiParamValues[] = vsprintf($paramSpecs[$position][$ppCount], $multiParamsForPosition);
                }
                $topParameters[] = implode($paramSpecs[$position]['combinedby'], $multiParamValues);
            } elseif ($paramSpecs[$position] !== null) {
                $ppCount = count($paramsForPosition);
                if (!isset($paramSpecs[$position][$ppCount])) {
                    throw new Exception\RuntimeException(sprintf('A number of parameters (%d) was found that is not supported by this specification', $ppCount));
                }
                $topParameters[] = vsprintf($paramSpecs[$position][$ppCount], $paramsForPosition);
            } else {
                $topParameters[] = $paramsForPosition;
            }
        }

        return vsprintf($specificationString, $topParameters);
    }

    /**
     * Render table with alias in from/join parts
     */
    protected function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    /**
     * Assemble marked SQL by replacing identifier markers with quotes
     * and value markers with either bound parameters or quoted values.
     */
    protected function assembleSqlWithValues(
        string $sql,
        array $values,
        PlatformInterface $platform,
        ?ParameterContainer $parameterContainer = null,
        string $paramPrefix = 'param',
        ?DriverInterface $driver = null
    ): string {
        // Replace identifier markers with actual quotes
        $quote = $platform->getQuoteIdentifierSymbol();
        $sql = str_replace([PreparableSqlInterface::P_LQUOTE, PreparableSqlInterface::P_RQUOTE], $quote, $sql);

        if ($values === []) {
            return $sql;
        }

        // Fast path: single value with no parameter container (most common case)
        if ($parameterContainer === null && count($values) === 1 && !$values[0] instanceof SelectArgument) {
            return str_replace(PreparableSqlInterface::P_VALUE, $this->quoteValueForSql($values[0], $platform), $sql);
        }

        $fullPrefix = $this->processInfo['paramPrefix'] . $paramPrefix;
        $paramIndex = 1;

        foreach ($values as $value) {
            if ($value instanceof SelectArgument) {
                $subSql = $this->processSubSelectForAssembly($value, $platform, $driver, $parameterContainer, $fullPrefix . 'sub' . $paramIndex);
                $sql = preg_replace(self::SELECT_PATTERN, $subSql, $sql, 1);
                $paramIndex++;
            } elseif ($parameterContainer !== null && $driver !== null) {
                $paramName = $fullPrefix . $paramIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $sql = preg_replace(self::VALUE_PATTERN, $driver->formatParameterName($paramName), $sql, 1);
            } else {
                $quotedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $this->quoteValueForSql($value, $platform));
                $sql = preg_replace(self::VALUE_PATTERN, $quotedValue, $sql, 1);
            }
        }

        return $sql;
    }

    /**
     * Process a Select argument for assembly into SQL.
     */
    protected function processSubSelectForAssembly(
        SelectArgument $selectArg,
        PlatformInterface $platform,
        ?DriverInterface $driver,
        ?ParameterContainer $parameterContainer,
        string $paramPrefix
    ): string {
        $select = $selectArg->getValue();

        if ($select instanceof SqlInterface) {
            if ($parameterContainer !== null) {
                $this->processInfo['subselectCount']++;
                $select->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
                $select->processInfo['paramPrefix'] = 'subselect' . $this->processInfo['subselectCount'];

                $sql = '(' . $select->buildSqlString($platform, $driver, $parameterContainer) . ')';
                $this->processInfo['subselectCount'] = $select->processInfo['subselectCount'];

                return $sql;
            }
            return '(' . $select->getSqlString($platform) . ')';
        }

        return $this->processExpression($select, $platform, $driver, $parameterContainer, $paramPrefix);
    }

    /**
     * Quote a value for direct SQL embedding.
     */
    protected function quoteValueForSql(mixed $value, PlatformInterface $platform): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $platform->quoteTrustedValue($value);
    }

    /**
     * Process an expression into SQL.
     */
    protected function processExpression(
        ExpressionInterface $expression,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null
    ): string {
        static $runtimeExpressionPrefix = 0;

        $expressionData = $expression->getExpressionData();
        $specification = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ($expressionValues === []) {
            return str_replace('%%', '%', $specification);
        }

        if ($namedParameterPrefix === null || $namedParameterPrefix === '') {
            $namedParameterPrefix = $parameterContainer ? 'expr' . $runtimeExpressionPrefix++ . 'Param' : '';
        } else {
            $namedParameterPrefix = $this->processInfo['paramPrefix'] . str_replace([' ', "\t", "\n", "\r"], '__', $namedParameterPrefix);
        }

        $this->instanceParameterIndex[$namedParameterPrefix] ??= 1;
        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];

        // Use marker-based processing if specification contains markers
        if (str_contains($specification, PreparableSqlInterface::P_LQUOTE)
            || str_contains($specification, PreparableSqlInterface::P_VALUE)
            || str_contains($specification, PreparableSqlInterface::P_SELECT)) {
            return $this->processExpressionWithMarkers(
                $specification,
                $expressionValues,
                $platform,
                $driver,
                $parameterContainer,
                $namedParameterPrefix,
                $expressionParamIndex
            );
        }

        // Legacy vsprintf path for backwards compatibility
        $values = [];
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $values[] = $parameterContainer !== null
                    ? $this->processParameterValue($argument->getValue(), $namedParameterPrefix, $expressionParamIndex, $driver, $parameterContainer)
                    : $platform->quoteValue((string) $argument->getValue());
            } elseif ($argument instanceof Argument\Identifier) {
                $values[] = $platform->quoteIdentifierInFragment($argument->getValue());
            } elseif ($argument instanceof Argument\Literal) {
                $values[] = $argument->getValue();
            } elseif ($argument instanceof SelectArgument) {
                $select = $argument->getValue();
                $values[] = $select instanceof Select
                    ? '(' . $this->processSubSelect($select, $platform, $driver, $parameterContainer) . ')'
                    : $this->processExpression($select, $platform, $driver, $parameterContainer, "{$namedParameterPrefix}subpart");
            }
        }

        return vsprintf($specification, $values);
    }

    /**
     * Process expression using marker-based format.
     */
    protected function processExpressionWithMarkers(
        string $specification,
        array $expressionValues,
        PlatformInterface $platform,
        ?DriverInterface $driver,
        ?ParameterContainer $parameterContainer,
        string $namedParameterPrefix,
        int &$expressionParamIndex
    ): string {
        $sql = strtr($specification, [
            PreparableSqlInterface::P_LQUOTE => $platform->getQuoteIdentifierSymbol(),
            PreparableSqlInterface::P_RQUOTE => $platform->getQuoteIdentifierSymbol(),
        ]);

        // Collect scalar values from arguments
        $scalarValues = [];
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $scalarValues[] = $argument->getValue();
            } elseif ($argument instanceof Argument\Values) {
                foreach ($argument->getValue() as $v) {
                    $scalarValues[] = $v;
                }
            } elseif ($argument instanceof SelectArgument) {
                $scalarValues[] = $argument;
            }
        }

        foreach ($scalarValues as $value) {
            if ($value instanceof SelectArgument) {
                $subSql = $this->processSubSelectForAssembly($value, $platform, $driver, $parameterContainer, $namedParameterPrefix . 'sub' . $expressionParamIndex);
                $sql = preg_replace(self::SELECT_PATTERN, $subSql, $sql, 1);
                $expressionParamIndex++;
            } elseif ($parameterContainer !== null && $driver !== null) {
                $paramName = $namedParameterPrefix . $expressionParamIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $sql = preg_replace(self::VALUE_PATTERN, $driver->formatParameterName($paramName), $sql, 1);
            } else {
                $quotedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $this->quoteValueForSql($value, $platform));
                $sql = preg_replace(self::VALUE_PATTERN, $quotedValue, $sql, 1);
            }
        }

        return $sql;
    }

    /**
     * Process a parameter value for bound parameters.
     */
    protected function processParameterValue(
        mixed $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
        ?DriverInterface $driver,
        ParameterContainer $parameterContainer
    ): string {
        $name = $namedParameterPrefix . $expressionParamIndex++;
        $parameterContainer->offsetSet($name, $value);

        return $driver->formatParameterName($name);
    }

    /**
     * Process a subselect query.
     */
    protected function processSubSelect(
        Select $subselect,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        if ($this instanceof PlatformDecoratorInterface) {
            $decorator = clone $this;
            $decorator->setSubject($subselect);
        } else {
            $decorator = $subselect;
        }

        if ($parameterContainer !== null) {
            $processInfoContext = $decorator instanceof PlatformDecoratorInterface ? $subselect : $decorator;
            $this->processInfo['subselectCount']++;
            $processInfoContext->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
            $processInfoContext->processInfo['paramPrefix'] = 'subselect' . $processInfoContext->processInfo['subselectCount'];

            $sql = $decorator->buildSqlString($platform, $driver, $parameterContainer);
            $this->processInfo['subselectCount'] = $decorator->processInfo['subselectCount'];

            return $sql;
        }

        return $decorator->buildSqlString($platform, $driver, $parameterContainer);
    }

    /**
     * Process JOIN clauses.
     */
    protected function processJoin(
        ?Join $joins,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): ?array {
        if ($joins === null || $joins->count() === 0) {
            return null;
        }

        $joinSpecArgArray = [];
        foreach ($joins->getJoins() as $j => $join) {
            $joinAs = null;
            $joinNameValue = $join['name'];

            if (is_array($joinNameValue)) {
                $joinName = current($joinNameValue);
                $joinAs = $platform->quoteIdentifier(key($joinNameValue));
            } else {
                $joinName = $joinNameValue;
            }

            if ($joinName instanceof Expression) {
                $joinName = $joinName->getExpression();
            } elseif ($joinName instanceof TableIdentifier) {
                $joinName = $joinName->getTableAndSchema();
                $joinName = ($joinName[1] ? $platform->quoteIdentifier($joinName[1]) . $platform->getIdentifierSeparator() : '')
                    . $platform->quoteIdentifier($joinName[0]);
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

            $joinSpecArgArray[$j] = [strtoupper($join['type']), $this->renderTable($joinName, $joinAs)];

            if ($join['on'] instanceof Predicate\PredicateInterface) {
                $values = [];
                $sql = $join['on']->toSqlPart($values);
                $joinSpecArgArray[$j][] = $this->assembleSqlWithValues($sql, $values, $platform, $parameterContainer, 'join' . ($j + 1) . 'part', $driver);
            } elseif ($join['on'] instanceof ExpressionInterface) {
                $joinSpecArgArray[$j][] = $this->processExpression($join['on'], $platform, $driver, $parameterContainer, 'join' . ($j + 1) . 'part');
            } else {
                $joinSpecArgArray[$j][] = $platform->quoteIdentifierInFragment($join['on'], ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>']);
            }
        }

        return [$joinSpecArgArray];
    }

    /**
     * Resolve a column value for SQL.
     */
    protected function resolveColumnValue(
        Select|array|string|int|bool|ExpressionInterface|null $column,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null
    ): string {
        $namedParameterPrefix = $namedParameterPrefix ? $this->processInfo['paramPrefix'] . $namedParameterPrefix : $namedParameterPrefix;
        $isIdentifier = false;
        $fromTable = '';

        if (is_array($column)) {
            $isIdentifier = (bool) ($column['isIdentifier'] ?? false);
            $fromTable = $column['fromTable'] ?? '';
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
     * Resolve a table reference for SQL.
     */
    protected function resolveTable(
        Select|string|TableIdentifier|null $table,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string|array|null {
        $schema = null;

        if ($table instanceof TableIdentifier) {
            [$table, $schema] = $table->getTableAndSchema();
        }

        if ($table instanceof Select) {
            $table = '(' . $this->processSubSelect($table, $platform, $driver, $parameterContainer) . ')';
        } elseif ($table) {
            $table = $platform->quoteIdentifier($table);
        }

        if ($schema && $table) {
            $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
        }

        return $table;
    }

    /**
     * Copy variables from the subject into the local properties (for platform decorators).
     */
    protected function localizeVariables(): void
    {
        if (!$this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
