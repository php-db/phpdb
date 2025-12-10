<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92 as DefaultAdapterPlatform;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
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
use function key;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function strtr;
use function strtoupper;
use function vsprintf;

abstract class AbstractSql implements SqlInterface
{
    public ?object $subject = null;

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
    #[Override]
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

        $sql = '';

        foreach ($this->specifications as $name => $specification) {
            $result = $this->{'process' . $name}(
                $platform,
                $driver,
                $parameterContainer
            );

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
     * Render table with alias in from/join parts
     *
     * @todo move TableIdentifier concatenation here
     */
    protected function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    /**
     * Assemble marked SQL by replacing identifier markers with quotes
     * and value markers with either bound parameters or quoted values.
     *
     * @param string $sql SQL with {"identifier"}, {?}, and {SQL} markers
     * @param array $values Values to substitute for markers
     * @param PlatformInterface $platform Platform for quoting
     * @param ParameterContainer|null $parameterContainer If set, use bound parameters
     * @param string $paramPrefix Prefix for parameter names
     * @param DriverInterface|null $driver Driver for formatting parameter names
     * @return string Assembled SQL
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
        $sql = strtr($sql, [
            PreparableSqlInterface::P_LQUOTE => $platform->getQuoteIdentifierSymbol(),
            PreparableSqlInterface::P_RQUOTE => $platform->getQuoteIdentifierSymbol(),
        ]);

        // Prepend processInfo prefix (e.g., 'subselect1') to ensure unique parameter names
        $fullPrefix = $this->processInfo['paramPrefix'] . $paramPrefix;

        // Process values - separate scalar values from Select arguments
        $paramIndex = 1;
        foreach ($values as $value) {
            if ($value instanceof SelectArgument) {
                // Process subquery
                $subSql = $this->processSubSelectForAssembly(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer,
                    $fullPrefix . 'sub' . $paramIndex
                );
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_SELECT, '/') . '/', $subSql, $sql, 1);
                $paramIndex++;
            } elseif ($parameterContainer !== null && $driver !== null) {
                // Use bound parameters for scalar values
                $paramName = $fullPrefix . $paramIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $placeholder = $driver->formatParameterName($paramName);
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_VALUE, '/') . '/', $placeholder, $sql, 1);
            } else {
                // Quote values directly
                $quotedValue = $this->quoteValueForSql($value, $platform);
                // Escape special regex characters in replacement
                $quotedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $quotedValue);
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_VALUE, '/') . '/', $quotedValue, $sql, 1);
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
            // Build the subquery SQL - wrap in parentheses for subqueries
            if ($parameterContainer !== null) {
                // Set up the parameter prefix on the subquery for LIMIT/OFFSET etc.
                $this->processInfo['subselectCount']++;
                $select->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
                $select->processInfo['paramPrefix'] = 'subselect' . $this->processInfo['subselectCount'];

                $sql = '(' . $select->buildSqlString($platform, $driver, $parameterContainer) . ')';

                // Propagate back any nested subselect count
                $this->processInfo['subselectCount'] = $select->processInfo['subselectCount'];

                return $sql;
            }
            return '(' . $select->getSqlString($platform) . ')';
        }

        // For ExpressionInterface, process it without extra parentheses
        // (the expression itself may already contain them if needed)
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

        // Quote all values including integers/floats for consistency
        return $platform->quoteTrustedValue($value);
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
        static $runtimeExpressionPrefix = 0;

        $expressionData   = $expression->getExpressionData();
        $specification    = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ($expressionValues === []) {
            return str_replace('%%', '%', $specification);
        }

        if ($namedParameterPrefix === null || $namedParameterPrefix === '') {
            $namedParameterPrefix = $parameterContainer
                ? 'expr' . $runtimeExpressionPrefix++ . 'Param'
                : '';
        } else {
            $namedParameterPrefix = $this->processInfo['paramPrefix']
                . str_replace([' ', "\t", "\n", "\r"], '__', $namedParameterPrefix);
        }

        if (! isset($this->instanceParameterIndex[$namedParameterPrefix])) {
            $this->instanceParameterIndex[$namedParameterPrefix] = 1;
        }

        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];

        // Detect if specification uses new marker format (contains {"} or {?} or {SQL})
        $usesMarkers = str_contains($specification, PreparableSqlInterface::P_LQUOTE)
            || str_contains($specification, PreparableSqlInterface::P_VALUE)
            || str_contains($specification, PreparableSqlInterface::P_SELECT);

        if ($usesMarkers) {
            // New marker-based processing path
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

        // Legacy vsprintf path
        $expressionValues = $this->flattenExpressionValues($expressionValues);
        $values           = [];

        foreach ($expressionValues as $vIndex => $argument) {
            $values[] = match (true) {
                $argument instanceof Value => $parameterContainer instanceof ParameterContainer
                    ? $this->processExpressionParameterName(
                        $argument->getValue(),
                        $namedParameterPrefix,
                        $expressionParamIndex,
                        $driver,
                        $parameterContainer
                    )
                    : $platform->quoteValue((string) $argument->getValue()),
                $argument instanceof Identifier => $platform->quoteIdentifierInFragment($argument->getValue()),
                $argument instanceof Literal => $argument->getValue(),
                $argument instanceof Values => $this->processValuesArgument(
                    $argument,
                    $expressionParamIndex,
                    $namedParameterPrefix,
                    $platform,
                    $driver,
                    $parameterContainer
                ),
                $argument instanceof Identifiers => $this->processIdentifiersArgument($argument, $platform),
                $argument instanceof SelectArgument => $this->processExpressionOrSelect(
                    $argument,
                    $namedParameterPrefix,
                    $vIndex,
                    $platform,
                    $driver,
                    $parameterContainer
                ),
                default => throw new Exception\InvalidArgumentException('Unknown argument type'),
            };
        }

        return vsprintf($specification, $values);
    }

    /**
     * Process expression using the new marker-based format.
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
        // Replace identifier markers with actual quotes
        $sql = strtr($specification, [
            PreparableSqlInterface::P_LQUOTE => $platform->getQuoteIdentifierSymbol(),
            PreparableSqlInterface::P_RQUOTE => $platform->getQuoteIdentifierSymbol(),
        ]);

        // Collect scalar values from arguments
        $scalarValues = [];
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $scalarValues[] = $argument->getValue();
            } elseif ($argument instanceof Values) {
                foreach ($argument->getValue() as $v) {
                    $scalarValues[] = $v;
                }
            } elseif ($argument instanceof SelectArgument) {
                $scalarValues[] = $argument;
            }
        }

        // Process values and subqueries
        foreach ($scalarValues as $value) {
            if ($value instanceof SelectArgument) {
                $subSql = $this->processSubSelectForAssembly(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer,
                    $namedParameterPrefix . 'sub' . $expressionParamIndex
                );
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_SELECT, '/') . '/', $subSql, $sql, 1);
                $expressionParamIndex++;
            } elseif ($parameterContainer !== null && $driver !== null) {
                $paramName = $namedParameterPrefix . $expressionParamIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $placeholder = $driver->formatParameterName($paramName);
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_VALUE, '/') . '/', $placeholder, $sql, 1);
            } else {
                $quotedValue = $this->quoteValueForSql($value, $platform);
                $quotedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $quotedValue);
                $sql = preg_replace('/' . preg_quote(PreparableSqlInterface::P_VALUE, '/') . '/', $quotedValue, $sql, 1);
            }
        }

        return $sql;
    }

    /**
     * Flattens expression values, expanding Values arguments
     *
     * @param ArgumentInterface[] $arguments
     * @return ArgumentInterface[]
     */
    protected function flattenExpressionValues(array $arguments): array
    {
        $hasValues = false;
        foreach ($arguments as $argument) {
            if ($argument instanceof Values) {
                $hasValues = true;
                break;
            }
        }

        if (! $hasValues) {
            return $arguments;
        }

        $values = [];
        foreach ($arguments as $argument) {
            if ($argument instanceof Values) {
                foreach ($argument->getValue() as $v) {
                    $values[] = new Value($v);
                }
            } else {
                $values[] = $argument;
            }
        }

        return $values;
    }

    protected function processExpressionOrSelect(
        ArgumentInterface $argument,
        string $namedParameterPrefix,
        int $vIndex,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $value = $argument->getValue();

        return match (true) {
            $value instanceof Select => '('
                . $this->processSubSelect($value, $platform, $driver, $parameterContainer)
                . ')',
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

    protected function processValuesArgument(
        ArgumentInterface $argument,
        int &$expressionParamIndex,
        string $namedParameterPrefix,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $values          = $argument->getValue();
        $processedValues = [];

        if ($parameterContainer instanceof ParameterContainer) {
            foreach ($values as $value) {
                $processedValues[] = $this->processExpressionParameterName(
                    $value,
                    $namedParameterPrefix,
                    $expressionParamIndex,
                    $driver,
                    $parameterContainer
                );
            }
        } else {
            foreach ($values as $value) {
                $processedValues[] = $platform->quoteValue((string) $value);
            }
        }

        return implode(', ', $processedValues);
    }

    protected function processIdentifiersArgument(
        ArgumentInterface $argument,
        PlatformInterface $platform
    ): string {
        $identifiers          = $argument->getValue();
        $processedIdentifiers = [];

        foreach ($identifiers as $identifier) {
            $processedIdentifiers[] = $platform->quoteIdentifierInFragment($identifier);
        }

        return implode(', ', $processedIdentifiers);
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

        if ($parameterContainer instanceof ParameterContainer) {
            $processInfoContext = $decorator instanceof PlatformDecoratorInterface ? $subselect : $decorator;
            $this->processInfo['subselectCount']++;
            $processInfoContext->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
            $processInfoContext->processInfo['paramPrefix']    = 'subselect'
                . $processInfoContext->processInfo['subselectCount'];

            $sql                                 = $decorator->buildSqlString($platform, $driver, $parameterContainer);
            $this->processInfo['subselectCount'] = $decorator->processInfo['subselectCount'];

            return $sql;
        }

        return $decorator->buildSqlString($platform, $driver, $parameterContainer);
    }

    /**
     * @return null|string[][][] Null if no joins present, array of JOIN statements otherwise
     */
    protected function processJoin(
        ?Join $joins,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): array|null {
        if ($joins === null || $joins->count() === 0) {
            return null;
        }

        $joinSpecArgArray = [];
        foreach ($joins->getJoins() as $j => $join) {
            $joinAs        = null;
            $joinNameValue = $join['name'];
            if (is_array($joinNameValue)) {
                $joinName = current($joinNameValue);
                $joinAs   = $platform->quoteIdentifier(key($joinNameValue));
            } else {
                $joinName = $joinNameValue;
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

            if ($join['on'] instanceof Predicate\PredicateInterface) {
                $values = [];
                $sql = $join['on']->toSqlPart($values);
                $joinSpecArgArray[$j][] = $this->assembleSqlWithValues(
                    $sql,
                    $values,
                    $platform,
                    $parameterContainer,
                    'join' . ($j + 1) . 'part',
                    $driver
                );
            } elseif ($join['on'] instanceof ExpressionInterface) {
                $joinSpecArgArray[$j][] = $this->processExpression(
                    $join['on'],
                    $platform,
                    $driver,
                    $parameterContainer,
                    'join' . ($j + 1) . 'part'
                );
            } else {
                $joinSpecArgArray[$j][] = $platform->quoteIdentifierInFragment(
                    $join['on'],
                    ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>']
                );
            }
        }

        return [$joinSpecArgArray];
    }

    protected function resolveColumnValue(
        Select|array|string|int|bool|ExpressionInterface|null $column,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null
    ): string {
        $namedParameterPrefix = $namedParameterPrefix
            ? $this->processInfo['paramPrefix'] . $namedParameterPrefix
            : $namedParameterPrefix;
        $isIdentifier         = false;
        $fromTable            = '';
        if (is_array($column)) {
            $isIdentifier = (bool) ($column['isIdentifier'] ?? false);
            $fromTable    = $column['fromTable'] ?? '';
            $column       = $column['column'];
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
     * Copy variables from the subject into the local properties
     */
    protected function localizeVariables(): void
    {
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
