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
 * @psalm-type ColumnValue = Select|ExpressionInterface|string|int|float|bool|null
 * @psalm-type ColumnReference = array{
 *     column: ColumnValue,
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
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $adapterPlatform ??= new DefaultAdapterPlatform();

        return $this->buildSqlString($adapterPlatform);
    }

    /**
     * @throws Exception\RuntimeException
     */
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
            return vsprintf($specifications, $this->flattenParameterList($parameters));
        }

        [$format, $parameterSpecifications] = $this->matchSpecification($specifications, count($parameters));

        $topParameters = [];
        foreach ($parameters as $position => $parameter) {
            $topParameters[] = $this->renderParameter($parameterSpecifications[$position] ?? null, $parameter);
        }

        return vsprintf($format, $topParameters);
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

    /**
     * Substitute values into the numbered placeholders of a string specification
     *
     * @throws Exception\RuntimeException When the named specification is not a format string.
     */
    protected function formatSpecification(string $name, string ...$values): string
    {
        $specification = $this->specifications[$name] ?? null;

        if (! is_string($specification)) {
            throw Exception\RuntimeException::forStringSpecificationRequired($name);
        }

        $placeholders = [];
        $position     = 0;
        foreach ($values as $_) {
            $placeholders[] = '%' . ++$position . '$s';
        }

        return str_replace($placeholders, $values, $specification);
    }

    protected function localizeVariables(): void
    {
        if (! $this instanceof PlatformDecoratorInterface || null === $this->subject) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @staticvar int $runtimeExpressionPrefix
     * @throws Exception\RuntimeException
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
                    && $driver instanceof DriverInterface
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

    /**
     * Render each expression in a list against the platform
     *
     * @param array<array-key, ExpressionInterface> $expressions
     * @return list<string>
     * @throws Exception\RuntimeException
     */
    protected function processExpressionList(array $expressions, PlatformInterface $platform): array
    {
        $sqls = [];
        foreach ($expressions as $expression) {
            $sqls[] = $this->processExpression($expression, $platform);
        }

        return $sqls;
    }

    /**
     * @throws Exception\RuntimeException
     */
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
        int|float|string|bool|null $value,
        string $namedParameterPrefix,
        int &$expressionParamIndex,
        DriverInterface $driver,
        ParameterContainer $parameterContainer,
    ): string {
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
     * @throws Exception\RuntimeException
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
            $joinAs   = null;
            $joinName = $join['name'];
            if (is_array($joinName)) {
                $alias    = array_key_first($joinName);
                $joinAs   = $platform->quoteIdentifier($alias);
                $joinName = $joinName[$alias] ?? '';
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

    /**
     * @throws Exception\RuntimeException
     */
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
     * @param ColumnReference|ColumnValue $column
     * @throws Exception\RuntimeException
     */
    protected function resolveColumnValue(
        Select|array|string|int|float|bool|ExpressionInterface|null $column,
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
            ? $fromTable . $platform->quoteIdentifierInFragment((string) $column)
            : $platform->quoteValue((string) $column);
    }

    /**
     * @return ($table is null ? null : string)
     * @throws Exception\RuntimeException
     */
    protected function resolveTable(
        Select|string|TableIdentifier|null $table,
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?string {
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

    /**
     * @param array<array-key, string|array<array-key, mixed>> $values
     * @return list<string>
     * @throws Exception\RuntimeException When a value is a nested list rather than a string.
     */
    private function flattenParameterList(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw Exception\RuntimeException::forUnsupportedParameterCountOf(count($value));
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param array<int|string, string> $parameterSpecification
     * @param array<array-key, string|array<array-key, mixed>> $values
     * @throws Exception\RuntimeException When no format accepts the number of values.
     */
    private function formatParameterList(array $parameterSpecification, array $values): string
    {
        $count  = count($values);
        $format = $parameterSpecification[$count] ?? throw Exception\RuntimeException::forUnsupportedParameterCountOf(
                $count,
            );

        return vsprintf($format, $this->flattenParameterList($values));
    }

    /**
     * @param array<string, list<ParameterSpecification>> $specifications
     * @return array{string, list<ParameterSpecification>}
     * @throws Exception\RuntimeException When no specification takes the number of parameters given.
     */
    private function matchSpecification(array $specifications, int $parameterCount): array
    {
        foreach ($specifications as $format => $parameterSpecifications) {
            if (count($parameterSpecifications) === $parameterCount) {
                return [$format, $parameterSpecifications];
            }
        }

        throw Exception\RuntimeException::forUnsupportedParameterCount();
    }

    private function quoteJoinTableIdentifier(TableIdentifier $identifier, PlatformInterface $platform): string
    {
        [$table, $schema] = $identifier->getTableAndSchema();

        return (
            ($schema ? $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() : '')
                . $platform->quoteIdentifier($table)
        );
    }

    /**
     * @param ParameterSpecification $parameterSpecification
     * @param string|array<array-key, string|array<array-key, string>> $parameter
     * @throws Exception\RuntimeException
     */
    private function renderParameter(?array $parameterSpecification, string|array $parameter): string
    {
        if (null === $parameterSpecification) {
            return is_string($parameter)
                ? $parameter
                : throw Exception\RuntimeException::forUnsupportedParameterCountOf(count($parameter));
        }

        $combinedBy = $parameterSpecification['combinedby'] ?? null;
        if (null === $combinedBy) {
            return $this->formatParameterList($parameterSpecification, (array) $parameter);
        }

        $rendered = [];
        foreach ((array) $parameter as $values) {
            $rendered[] = $this->formatParameterList($parameterSpecification, (array) $values);
        }

        return implode($combinedBy, $rendered);
    }
}
