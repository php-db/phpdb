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
use PhpDb\Sql\Clause\Join;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function strpos;
use function strtr;
use function substr_replace;
use function vsprintf;

abstract class AbstractSql implements SqlInterface
{
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
                    $ppCount = is_array($multiParamsForPosition) ? count($multiParamsForPosition) : 1;
                    if (! is_array($multiParamsForPosition)) {
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
     * Render table with alias in from/join parts
     */
    protected function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    /**
     * Quote marked SQL by replacing identifier markers with quotes
     * and value markers with either bound parameters or quoted values.
     */
    protected function quoteSqlString(
        string $sql,
        array $values,
        PlatformInterface $platform,
        ?ParameterContainer $parameterContainer = null,
        string $paramPrefix = 'param',
        ?DriverInterface $driver = null
    ): string {
        if ($values === []) {
            return $sql;
        }

        if ($parameterContainer === null || $driver === null) {
            if (! str_contains($sql, PreparableSqlInterface::P_SELECT)) {
                return $this->quoteValuesDirect($sql, $values, $platform);
            }

            return $this->quoteWithSubSelects($sql, $values, $platform, null, null, $paramPrefix);
        }

        if (! str_contains($sql, PreparableSqlInterface::P_SELECT)) {
            return $this->quoteWithParameters($sql, $values, $driver, $parameterContainer, $paramPrefix);
        }

        return $this->quoteWithSubSelects($sql, $values, $platform, $driver, $parameterContainer, $paramPrefix);
    }

    /**
     * Quote values directly into SQL string (no prepared statement).
     */
    private function quoteValuesDirect(string $sql, array $values, PlatformInterface $platform): string
    {
        return vsprintf($sql, $values);
    }

    /**
     * Replace value markers with bound parameter placeholders.
     */
    private function quoteWithParameters(
        string $sql,
        array $values,
        DriverInterface $driver,
        ParameterContainer $parameterContainer,
        string $paramPrefix
    ): string {
        $fullPrefix = $this->processInfo['paramPrefix'] . $paramPrefix;
        $paramIndex = 1;

        foreach ($values as $value) {
            $paramName = $fullPrefix . $paramIndex++;
            $parameterContainer->offsetSet($paramName, $value);
            $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
            if ($pos !== false) {
                $sql = substr_replace($sql, $driver->formatParameterName($paramName), $pos, 3); // 3 = strlen('{?}')
            }
        }

        return $sql;
    }

    /**
     * Handle SQL with subselects - requires position-aware replacement.
     */
    private function quoteWithSubSelects(
        string $sql,
        array $values,
        PlatformInterface $platform,
        ?DriverInterface $driver,
        ?ParameterContainer $parameterContainer,
        string $paramPrefix
    ): string {
        $fullPrefix = $this->processInfo['paramPrefix'] . $paramPrefix;
        $paramIndex = 1;
        $hasPrepare = $parameterContainer !== null && $driver !== null;

        foreach ($values as $value) {
            if ($value instanceof SelectArgument) {
                $subSql = $this->processSubSelectForAssembly(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer,
                    $fullPrefix . 'sub' . $paramIndex
                );
                $pos    = strpos($sql, PreparableSqlInterface::P_SELECT);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $subSql, $pos, 5);
                }
                $paramIndex++;
            } elseif ($hasPrepare) {
                $paramName = $fullPrefix . $paramIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $driver->formatParameterName($paramName), $pos, 3); // 3 = strlen('{?}')
                }
            } else {
                $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $this->quoteValueForSql($value, $platform), $pos, 3);
                }
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

        if ($select instanceof AbstractSql) {
            if ($parameterContainer !== null) {
                $this->processInfo['subselectCount']++;
                $select->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
                $select->processInfo['paramPrefix']    = 'subselect' . $this->processInfo['subselectCount'];

                $sql                                 = '(' . $select->buildSqlString(
                    $platform,
                    $driver,
                    $parameterContainer
                ) . ')';
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
        if (is_string($value) && is_numeric($value)) {
            return $value;
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

        $expressionData   = $expression->getExpressionData();
        $specification    = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ($expressionValues === []) {
            return str_replace('%%', '%', $specification);
        }

        if ($namedParameterPrefix === null || $namedParameterPrefix === '') {
            $namedParameterPrefix = $parameterContainer ? 'expr' . $runtimeExpressionPrefix++ . 'Param' : '';
        } else {
            $namedParameterPrefix = $this->processInfo['paramPrefix'] . str_replace(
                [' ', "\t", "\n", "\r"],
                '__',
                $namedParameterPrefix
            );
        }

        $this->instanceParameterIndex[$namedParameterPrefix] ??= 1;
        $expressionParamIndex                                  = &$this->instanceParameterIndex[$namedParameterPrefix];

        if (
            str_contains($specification, PreparableSqlInterface::P_LQUOTE)
            || str_contains($specification, PreparableSqlInterface::P_VALUE)
            || str_contains($specification, PreparableSqlInterface::P_SELECT)
        ) {
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

        $values = [];
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $values[] = $parameterContainer !== null
                    ? $this->processParameterValue(
                        $argument->getValue(),
                        $namedParameterPrefix,
                        $expressionParamIndex,
                        $driver,
                        $parameterContainer
                    )
                    : $platform->quoteValue((string) $argument->getValue());
            } elseif ($argument instanceof Argument\Identifier) {
                $values[] = $platform->quoteIdentifierInFragment($argument->getValue());
            } elseif ($argument instanceof Argument\Literal) {
                $values[] = $argument->getValue();
            } elseif ($argument instanceof SelectArgument) {
                $select   = $argument->getValue();
                $values[] = $select instanceof Select
                    ? '(' . $this->processSubSelect($select, $platform, $driver, $parameterContainer) . ')'
                    : $this->processExpression(
                        $select,
                        $platform,
                        $driver,
                        $parameterContainer,
                        "{$namedParameterPrefix}subpart"
                    );
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

        $scalarValues = [];
        $hasSubSelect = false;
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $scalarValues[] = $argument->getValue();
            } elseif ($argument instanceof Argument\Values) {
                foreach ($argument->getValue() as $v) {
                    $scalarValues[] = $v;
                }
            } elseif ($argument instanceof SelectArgument) {
                $scalarValues[] = $argument;
                $hasSubSelect   = true;
            }
        }

        if ($scalarValues === []) {
            return $sql;
        }

        // Fast path: no subselects - use position-based replacement
        if (! $hasSubSelect) {
            foreach ($scalarValues as $value) {
                $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
                if ($pos !== false) {
                    if ($parameterContainer !== null && $driver !== null) {
                        $paramName = $namedParameterPrefix . $expressionParamIndex++;
                        $parameterContainer->offsetSet($paramName, $value);
                        $sql = substr_replace(
                            $sql,
                            $driver->formatParameterName($paramName),
                            $pos,
                            3
                        ); // 3 = strlen('{?}')
                    } else {
                        $sql = substr_replace(
                            $sql,
                            $this->quoteValueForSql($value, $platform),
                            $pos,
                            3
                        ); // 3 = strlen('{?}')
                    }
                }
            }

            return $sql;
        }

        // Slow path: has subselects - need position-aware replacement
        foreach ($scalarValues as $value) {
            if ($value instanceof SelectArgument) {
                $subSql = $this->processSubSelectForAssembly(
                    $value,
                    $platform,
                    $driver,
                    $parameterContainer,
                    $namedParameterPrefix . 'sub' . $expressionParamIndex
                );
                $pos    = strpos($sql, PreparableSqlInterface::P_SELECT);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $subSql, $pos, 5); // 5 = strlen('{SQL}')
                }
                $expressionParamIndex++;
            } elseif ($parameterContainer !== null && $driver !== null) {
                $paramName = $namedParameterPrefix . $expressionParamIndex++;
                $parameterContainer->offsetSet($paramName, $value);
                $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $driver->formatParameterName($paramName), $pos, 3); // 3 = strlen('{?}')
                }
            } else {
                $pos = strpos($sql, PreparableSqlInterface::P_VALUE);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $this->quoteValueForSql($value, $platform), $pos, 3);
                }
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
            $processInfoContext->processInfo['paramPrefix']    =
                'subselect' . $processInfoContext->processInfo['subselectCount'];

            $sql                                 = $decorator->buildSqlString($platform, $driver, $parameterContainer);
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
            $tableId  = $join->name;
            $schema   = $tableId->getSchema();
            $joinName = $schema
                ? $platform->quoteIdentifier($schema)
                    . $platform->getIdentifierSeparator()
                    . $platform->quoteIdentifier($tableId->getTable())
                : $platform->quoteIdentifier($tableId->getTable());

            $joinAs = $tableId->getAlias() ? $platform->quoteIdentifier($tableId->getAlias()) : null;

            $joinSpecArgArray[$j] = [$join->type->value, $this->renderTable($joinName, $joinAs)];

            $q                      = $platform->getQuoteIdentifierSymbol();
            $sql                    = $join->on->prepareSqlString($q, $platform);
            $joinSpecArgArray[$j][] = $this->quoteSqlString(
                $sql,
                [],
                $platform,
                $parameterContainer,
                'join' . ($j + 1) . 'part',
                $driver
            );
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
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
