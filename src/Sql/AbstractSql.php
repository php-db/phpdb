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

use function array_key_first;
use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_string;
use function rtrim;
use function str_replace;
use function strtoupper;
use function vsprintf;

/**
 * @psalm-type ParameterSpecification = array<int|string, string>|null
 * @psalm-type Specification = string|array<string, list<ParameterSpecification>>
 * @psalm-type SqlParameters = array<array-key, string|array<array-key, string|array<array-key, string>>>
 * @psalm-type ColumnReference = array{
 *     column: Select|string|int|bool|ExpressionInterface|null,
 *     fromTable?: string,
 *     isIdentifier?: bool,
 * }
 * @psalm-import-type JoinSpecification from Join
 */
abstract class AbstractSql implements SqlInterface
{
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    /**
     * Specifications for Sql String generation
     *
     * @var array<string, Specification>
     */
    protected array $specifications = [];

    /**
     * Information used during processing
     *
     * @var array{paramPrefix: string, subselectCount: int}
     */
    protected array $processInfo = ['paramPrefix' => '', 'subselectCount' => 0];

    /** @var array<string, int> */
    protected array $instanceParameterIndex = [];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $adapterPlatform ??= new DefaultAdapterPlatform();

        return $this->buildSqlString($adapterPlatform);
    }

    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): string {
        $this->localizeVariables();

        $sqls = [];

        foreach ($this->specifications as $name => $specification) {
            /** @var SqlParameters|string|null $result */
            $result = $this->{"process{$name}"}(
                $platform,
                $driver,
                $parameterContainer,
            );

            if (is_array($result)) {
                $sqls[$name] = $this->createSqlFromSpecificationAndParameters($specification, $result);
                continue;
            }

            if (null !== $result) {
                $sqls[$name] = $result;
            }
        }

        return rtrim(implode(' ', $sqls), characters: "\n ,");
    }

    /**
     * @param Specification $specifications
     * @param SqlParameters $parameters
     * @throws Exception\RuntimeException
     */
    protected function createSqlFromSpecificationAndParameters(array|string $specifications, array $parameters): string
    {
        if (is_string($specifications)) {
            return vsprintf($specifications, $parameters);
        }

        $parametersCount     = count($parameters);
        $specificationString = null;
        $paramSpecs          = [];

        foreach ($specifications as $candidateString => $paramSpecs) {
            if (count($paramSpecs) !== $parametersCount) {
                continue;
            }

            $specificationString = (string) $candidateString;
            break;
        }

        if (null === $specificationString) {
            throw Exception\RuntimeException::forUnsupportedParameterCount();
        }

        $topParameters = [];
        foreach ($parameters as $position => $paramsForPosition) {
            if (null !== ($paramSpecs[$position]['combinedby'] ?? null)) {
                $multiParamValues = [];
                foreach ($paramsForPosition as $multiParamsForPosition) {
                    if (! is_array($multiParamsForPosition)) {
                        $multiParamsForPosition = [$multiParamsForPosition];
                    }

                    $ppCount = count($multiParamsForPosition);

                    if (null === ($paramSpecs[$position][$ppCount] ?? null)) {
                        throw Exception\RuntimeException::forUnsupportedParameterCountOf($ppCount);
                    }

                    $multiParamValues[] = vsprintf($paramSpecs[$position][$ppCount], $multiParamsForPosition);
                }

                $topParameters[] = implode($paramSpecs[$position]['combinedby'], $multiParamValues);
                continue;
            }

            if (null === $paramSpecs[$position]) {
                $topParameters[] = $paramsForPosition;
                continue;
            }

            $ppCount = count($paramsForPosition);
            if (null === ($paramSpecs[$position][$ppCount] ?? null)) {
                throw Exception\RuntimeException::forUnsupportedParameterCountOf($ppCount);
            }

            $topParameters[] = vsprintf($paramSpecs[$position][$ppCount], $paramsForPosition);
        }

        return vsprintf($specificationString, $topParameters);
    }

    /**
     * Flattens expression values, expanding Values arguments
     *
     * @param list<ArgumentInterface> $arguments
     * @return list<ArgumentInterface>
     */
    protected function flattenExpressionValues(array $arguments): array
    {
        $hasValues = false;
        foreach ($arguments as $argument) {
            if (! $argument instanceof Values) {
                continue;
            }

            $hasValues = true;
            break;
        }

        if (! $hasValues) {
            return $arguments;
        }

        $values = [];
        foreach ($arguments as $argument) {
            if (! $argument instanceof Values) {
                $values[] = $argument;
                continue;
            }

            foreach ($argument->getValue() as $v) {
                $values[] = new Value($v);
            }
        }

        return $values;
    }

    protected function localizeVariables(): void
    {
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @staticvar int $runtimeExpressionPrefix
     */
    protected function processExpression(
        ExpressionInterface $expression,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null,
    ): string {
        static $runtimeExpressionPrefix = 0;

        $expressionData   = $expression->getExpressionData();
        $specification    = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ([] === $expressionValues) {
            return str_replace('%%', replace: '%', subject: $specification);
        }

        $namedParameterPrefix = match (true) {
            null === $namedParameterPrefix || '' === $namedParameterPrefix => $parameterContainer
                ? 'expr' . $runtimeExpressionPrefix++ . 'Param'
                : '',
            default => $this->processInfo['paramPrefix']
                . str_replace([' ', "\t", "\n", "\r"], replace: '__', subject: $namedParameterPrefix),
        };

        $this->instanceParameterIndex[$namedParameterPrefix] ??= 1;

        $expressionParamIndex = &$this->instanceParameterIndex[$namedParameterPrefix];
        $expressionValues     = $this->flattenExpressionValues($expressionValues);
        $values               = [];

        foreach ($expressionValues as $vIndex => $argument) {
            $values[] = match (true) {
                $argument instanceof Value => $parameterContainer instanceof ParameterContainer
                    ? $this->processExpressionParameterName(
                        $argument->getValue(),
                        $namedParameterPrefix,
                        $expressionParamIndex,
                        $driver,
                        $parameterContainer,
                    )
                    : $platform->quoteValue((string) $argument->getValue()),
                $argument instanceof Identifier => $platform->quoteIdentifierInFragment($argument->getValue()),
                $argument instanceof Literal => $argument->getValue(),
                $argument instanceof Identifiers => $this->processIdentifiersArgument($argument, $platform),
                $argument instanceof SelectArgument => $this->processExpressionOrSelect(
                    $argument,
                    $namedParameterPrefix,
                    $vIndex,
                    $platform,
                    $driver,
                    $parameterContainer,
                ),
                default => throw Exception\InvalidArgumentException::forUnknownArgumentType(),
            };
        }

        return vsprintf($specification, $values);
    }

    protected function processExpressionOrSelect(
        ArgumentInterface $argument,
        string $namedParameterPrefix,
        int $vIndex,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): string {
        $value = $argument->getValue();

        return match (true) {
            $value instanceof Select => "({$this->processSubSelect($value, $platform, $driver, $parameterContainer)})",
            $value instanceof ExpressionInterface => $this->processExpression(
                $value,
                $platform,
                $driver,
                $parameterContainer,
                "{$namedParameterPrefix}{$vIndex}subpart",
            ),
            default => throw Exception\InvalidArgumentException::forInvalidArgumentType(),
        };
    }

    protected function processExpressionParameterName(
        int|float|string|bool $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
        DriverInterface $driver,
        ParameterContainer $parameterContainer,
    ): ?string {
        $name = $namedParameterPrefix . $expressionParamIndex++;
        $parameterContainer->offsetSet($name, $value);

        return $driver->formatParameterName($name);
    }

    protected function processIdentifiersArgument(
        ArgumentInterface $argument,
        PlatformInterface $platform,
    ): string {
        /** @var list<string> $identifiers */
        $identifiers          = $argument->getValue();
        $processedIdentifiers = [];

        foreach ($identifiers as $identifier) {
            $processedIdentifiers[] = $platform->quoteIdentifierInFragment($identifier);
        }

        return implode(', ', $processedIdentifiers);
    }

    /**
     * @return null|array{0: array<int, list<string>>} Null if no joins present, array of JOIN statements otherwise
     */
    protected function processJoin(
        ?Join $joins,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $joins || $joins->count() === 0) {
            return null;
        }

        $joinSpecArgArray = [];
        foreach ($joins->getJoins() as $j => $join) {
            $joinAs        = null;
            $joinNameValue = $join['name'];
            $joinName      = $joinNameValue;
            if (is_array($joinNameValue)) {
                $alias    = array_key_first($joinNameValue);
                $joinName = $joinNameValue[$alias];
                $joinAs   = $platform->quoteIdentifier($alias);
            }

            $joinName = match (true) {
                $joinName instanceof Expression => $joinName->getExpression(),
                $joinName instanceof TableIdentifier => $this->quoteJoinTableIdentifier($joinName, $platform),
                $joinName instanceof Select => "({$this->processSubSelect(
                    $joinName,
                    $platform,
                    $driver,
                    $parameterContainer,
                )})",
                default => $platform->quoteIdentifier($joinName),
            };

            $joinSpecArgArray[$j] = [
                strtoupper($join['type']),
                $this->renderTable($joinName, $joinAs),
            ];

            $joinSpecArgArray[$j][] = $join['on'] instanceof ExpressionInterface
                ? $this->processExpression(
                    $join['on'],
                    $platform,
                    $driver,
                    $parameterContainer,
                    'join' . ($j + 1) . 'part',
                )
                : $platform->quoteIdentifierInFragment(
                    $join['on'],
                    ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>'],
                );
        }

        return [$joinSpecArgArray];
    }

    protected function processSubSelect(
        Select $subselect,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): string {
        $decorator = $subselect;
        if ($this instanceof PlatformDecoratorInterface) {
            $decorator = clone $this;
            $decorator->setSubject($subselect);
        }

        if ($parameterContainer instanceof ParameterContainer) {
            $processInfoContext = $decorator instanceof PlatformDecoratorInterface ? $subselect : $decorator;
            $this->processInfo['subselectCount']++;
            $processInfoContext->processInfo['subselectCount'] = $this->processInfo['subselectCount'];
            $processInfoContext->processInfo['paramPrefix']    = "subselect{$processInfoContext->processInfo['subselectCount']}";

            $sql                                 = $decorator->buildSqlString($platform, $driver, $parameterContainer);
            $this->processInfo['subselectCount'] = $decorator->processInfo['subselectCount'];

            return $sql;
        }

        return $decorator->buildSqlString($platform, $driver, $parameterContainer);
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
     * @param ColumnReference|Select|string|int|bool|ExpressionInterface|null $column
     */
    protected function resolveColumnValue(
        Select|array|string|int|bool|ExpressionInterface|null $column,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?string $namedParameterPrefix = null,
    ): string {
        $namedParameterPrefix = $namedParameterPrefix
            ? $this->processInfo['paramPrefix'] . $namedParameterPrefix
            : $namedParameterPrefix;
        $isIdentifier = false;
        $fromTable    = '';
        if (is_array($column)) {
            $isIdentifier = $column['isIdentifier'] ?? false;
            $fromTable    = $column['fromTable'] ?? '';
            $column       = $column['column'];
        }

        if ($column instanceof ExpressionInterface) {
            return $this->processExpression($column, $platform, $driver, $parameterContainer, $namedParameterPrefix);
        }

        if ($column instanceof Select) {
            return "({$this->processSubSelect($column, $platform, $driver, $parameterContainer)})";
        }

        if (null === $column) {
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
        ?ParameterContainer $parameterContainer = null,
    ): string|array|null {
        $schema = null;
        if ($table instanceof TableIdentifier) {
            [$table, $schema] = $table->getTableAndSchema();
        }

        if ($table instanceof Select) {
            return "({$this->processSubSelect($table, $platform, $driver, $parameterContainer)})";
        }

        if (! $table) {
            return $table;
        }

        $table = $platform->quoteIdentifier($table);

        if ($schema) {
            $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
        }

        return $table;
    }

    private function quoteJoinTableIdentifier(TableIdentifier $identifier, PlatformInterface $platform): string
    {
        [$table, $schema] = $identifier->getTableAndSchema();

        return (
            ($schema ? $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() : '')
                . $platform->quoteIdentifier($table)
        );
    }
}
