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

use function get_object_vars;
use function is_array;
use function is_bool;
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

    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        return $this->buildSqlString($adapterPlatform ?? new DefaultAdapterPlatform());
    }

    /**
     * Build the SQL string. Must be implemented by subclasses.
     */
    abstract protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string;

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

        // Fast path: no parameters - return spec directly (no escaping needed)
        if ($expressionValues === []) {
            return $specification;
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
            str_contains($specification, '{"')
            || str_contains($specification, '%s')
            || str_contains($specification, '{SQL}')
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
        $q   = $platform->getQuoteIdentifierSymbol();
        $sql = strtr($specification, ['{"' => $q, '"}' => $q]);

        // Process each argument in order, replacing %s markers
        foreach ($expressionValues as $argument) {
            $pos = strpos($sql, '%s');
            if ($pos === false) {
                break;
            }

            if ($argument instanceof Argument\Identifier) {
                // Identifier: quote as identifier, not as value
                $sql = substr_replace($sql, $platform->quoteIdentifierInFragment($argument->getValue()), $pos, 2);
            } elseif ($argument instanceof Argument\Literal) {
                // Literal: insert as-is
                $sql = substr_replace($sql, $argument->getValue(), $pos, 2);
            } elseif ($argument instanceof SelectArgument) {
                // SubSelect: process the subselect
                $subSql = $this->processSubSelectForAssembly(
                    $argument,
                    $platform,
                    $driver,
                    $parameterContainer,
                    $namedParameterPrefix . 'sub' . $expressionParamIndex
                );
                // SubSelects use {SQL} marker, but if we're at %s, use that
                $sqlPos = strpos($sql, '{SQL}');
                if ($sqlPos !== false && $sqlPos < $pos) {
                    $sql = substr_replace($sql, $subSql, $sqlPos, 5);
                } else {
                    $sql = substr_replace($sql, $subSql, $pos, 2);
                }
                $expressionParamIndex++;
            } elseif ($argument instanceof Value) {
                // Value: bind as parameter or quote directly
                $value = $argument->getValue();
                if ($parameterContainer !== null && $driver !== null) {
                    $paramName = $namedParameterPrefix . $expressionParamIndex++;
                    $parameterContainer->offsetSet($paramName, $value);
                    $sql = substr_replace($sql, $driver->formatParameterName($paramName), $pos, 2);
                } else {
                    $sql = substr_replace($sql, $this->quoteValueForSql($value, $platform), $pos, 2);
                }
            } elseif ($argument instanceof Argument\Values) {
                // Values array: each value needs to be processed
                foreach ($argument->getValue() as $v) {
                    $pos = strpos($sql, '%s');
                    if ($pos === false) {
                        break;
                    }
                    if ($parameterContainer !== null && $driver !== null) {
                        $paramName = $namedParameterPrefix . $expressionParamIndex++;
                        $parameterContainer->offsetSet($paramName, $v);
                        $sql = substr_replace($sql, $driver->formatParameterName($paramName), $pos, 2);
                    } else {
                        $sql = substr_replace($sql, $this->quoteValueForSql($v, $platform), $pos, 2);
                    }
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
