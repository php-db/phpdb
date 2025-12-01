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
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_pad;
use function strtoupper;
use function vsprintf;

use const STR_PAD_LEFT;

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
     * @todo move TableIdentifier concatenation here
     */
    protected function renderTable(string $table, ?string $alias = null): string
    {
        return $alias ? "{$table} AS {$alias}" : $table;
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
            $namedParameterPrefix = 'expr'
                . str_pad((string) ++$runtimeExpressionPrefix, 4, '0', STR_PAD_LEFT)
                . 'Param';
        } else {
            $namedParameterPrefix = preg_replace('/\s/', '__', $namedParameterPrefix);
        }

        if (! isset($this->instanceParameterIndex[$namedParameterPrefix])) {
            $this->instanceParameterIndex[$namedParameterPrefix] = 1;
        }

        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];
        $expressionData       = $expression->getExpressionData();
        $specification        = $expressionData['spec'];
        $expressionValues     = $this->flattenExpressionValues($expressionData['values']);
        $values               = [];

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

        return vsprintf($specification, $values);
    }

    /**
     * Flattens expression values, expanding Values arguments
     *
     * @param ArgumentInterface[] $arguments
     * @return ArgumentInterface[]
     */
    protected function flattenExpressionValues(array $arguments): array
    {
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

    protected function processExpressionValue(
        ArgumentInterface $argument,
        int &$expressionParamIndex,
        string $namedParameterPrefix,
        int $vIndex,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
        return match (true) {
            $argument instanceof SelectArgument => $this->processExpressionOrSelect(
                $argument,
                $namedParameterPrefix,
                $vIndex,
                $platform,
                $driver,
                $parameterContainer
            ),
            $argument instanceof Identifier => $platform->quoteIdentifierInFragment($argument->getValue()),
            $argument instanceof Identifiers => $this->processIdentifiersArgument($argument, $platform),
            $argument instanceof Literal => $argument->getValue(),
            $argument instanceof Value => $parameterContainer instanceof ParameterContainer
                ? $this->processExpressionParameterName(
                    $argument->getValue(),
                    $namedParameterPrefix,
                    $expressionParamIndex,
                    $driver,
                    $parameterContainer
                )
                : $platform->quoteValue((string) $argument->getValue()),
            $argument instanceof Values => $this->processValuesArgument(
                $argument,
                $expressionParamIndex,
                $namedParameterPrefix,
                $platform,
                $driver,
                $parameterContainer
            ),
            default => throw new Exception\InvalidArgumentException('Unknown argument type'),
        };
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

        foreach ($values as $value) {
            if ($parameterContainer instanceof ParameterContainer) {
                $processedValues[] = $this->processExpressionParameterName(
                    $value,
                    $namedParameterPrefix,
                    $expressionParamIndex,
                    $driver,
                    $parameterContainer
                );
            } else {
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

        // process joins
        $joinSpecArgArray = [];
        foreach ($joins->getJoins() as $j => $join) {
            $joinAs = null;

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
