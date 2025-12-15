<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;

use function is_bool;
use function is_float;
use function is_int;
use function str_contains;
use function str_replace;
use function vsprintf;

/**
 * Builder context for generating SQL strings with optional parameter binding.
 *
 * When a driver and parameter container are provided, values are bound as
 * prepared statement parameters. Otherwise, values are quoted directly.
 */
final class PreparableSqlBuilder
{
    public readonly string $q;

    private int $paramIndex     = 1;
    private int $subselectCount = 0;

    public bool $inSubselect = false;

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly ?DriverInterface $driver = null,
        private readonly ?ParameterContainer $params = null,
        private readonly string $paramPrefix = 'p'
    ) {
        $this->q = $platform->getQuoteIdentifierSymbol();
    }

    /**
     * Check if parameter binding is active.
     */
    public function isParameterized(): bool
    {
        return $this->driver !== null && $this->params !== null;
    }

    /**
     * Bind a value as a parameter or quote it directly.
     *
     * @param mixed $value The value to bind or quote
     * @return string The parameter placeholder (e.g., :p1) or quoted value
     */
    public function bindValue(mixed $value): string
    {
        if ($this->isParameterized()) {
            $name = $this->paramPrefix . $this->paramIndex++;
            $this->params->offsetSet($name, $value);
            return $this->driver->formatParameterName($name);
        }

        return $this->quoteValue($value);
    }

    /**
     * Bind multiple values and return placeholders as comma-separated list.
     *
     * @param array $values The values to bind or quote
     * @return string Comma-separated placeholders or quoted values (e.g., ":p1, :p2" or "'a', 'b'")
     */
    public function bindValues(array $values): string
    {
        $result = '';
        $first  = true;
        foreach ($values as $value) {
            if (! $first) {
                $result .= ', ';
            }
            $first   = false;
            $result .= $this->bindValue($value);
        }
        return $result;
    }

    /**
     * Bind a value with a specific name (for LIMIT, OFFSET, etc.).
     *
     * @param string $name Parameter name
     * @param mixed $value The value to bind
     */
    public function bindNamedValue(string $name, mixed $value): void
    {
        if ($this->params !== null) {
            $this->params->offsetSet($name, $value);
        }
    }

    /**
     * Format a named parameter for the current driver.
     *
     * @param string $name Parameter name
     * @return string Formatted parameter (e.g., :name or ?)
     */
    public function formatParameterName(string $name): string
    {
        return $this->driver?->formatParameterName($name) ?? '?';
    }

    /**
     * Quote a value for direct SQL embedding.
     */
    public function quoteValue(mixed $value): string
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

        return $this->platform->quoteTrustedValue($value);
    }

    /**
     * Quote an identifier (column or table name).
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->q . $identifier . $this->q;
    }

    /**
     * Quote an identifier that may contain a table prefix (e.g., "table.column").
     */
    public function quoteIdentifierInFragment(string $identifier): string
    {
        return $this->platform->quoteIdentifierInFragment($identifier);
    }

    /**
     * Get the underlying platform.
     */
    public function getPlatform(): PlatformInterface
    {
        return $this->platform;
    }

    /**
     * Get the driver (for subselect processing).
     */
    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the parameter container (for subselect processing).
     */
    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->params;
    }

    /**
     * Create a child builder with a new prefix for nested contexts (e.g., subqueries).
     */
    public function withPrefix(string $prefix): self
    {
        $child                 = new self(
            $this->platform,
            $this->driver,
            $this->params,
            $this->paramPrefix . $prefix
        );
        $child->subselectCount = $this->subselectCount;
        $child->inSubselect    = $this->inSubselect;
        return $child;
    }

    /**
     * Quote an identifier with optional table prefix. Static for performance.
     */
    public static function quoteId(string $id, string $q): string
    {
        return str_contains($id, '.')
            ? $q . str_replace('.', $q . '.' . $q, $id) . $q
            : $q . $id . $q;
    }

    /**
     * Convert an Argument to SQL. Centralizes logic for all argument types.
     */
    public function argumentToSql(ArgumentInterface $argument): string
    {
        if ($argument instanceof Identifier) {
            return self::quoteId($argument->value, $this->q);
        }
        if ($argument instanceof Value) {
            return $this->bindValue($argument->value);
        }
        if ($argument instanceof Literal) {
            return $argument->value;
        }
        if ($argument instanceof Values) {
            return '(' . $this->bindValues($argument->value) . ')';
        }
        if ($argument instanceof SelectArgument) {
            // Note: Callers are responsible for wrapping in parentheses if needed
            return $argument->value instanceof Select
                ? $this->processSubSelect($argument->value)
                : $this->processExpression($argument->value);
        }

        // Fallback for any custom ArgumentInterface implementations
        return $argument->toSql($this);
    }

    /**
     * Process an expression into SQL.
     */
    public function processExpression(ExpressionInterface $expression): string
    {
        $expressionData   = $expression->getExpressionData();
        $specification    = $expressionData['spec'];
        $expressionValues = $expressionData['values'];

        if ($expressionValues === []) {
            return str_replace('%%', '%', $specification);
        }

        $values = [];
        foreach ($expressionValues as $argument) {
            if ($argument instanceof Value) {
                $values[] = $this->bindValue($argument->value);
            } elseif ($argument instanceof Values) {
                $values[] = '(' . $this->bindValues($argument->value) . ')';
            } elseif ($argument instanceof Identifier) {
                $values[] = self::quoteId($argument->value, $this->q);
            } elseif ($argument instanceof Literal) {
                $values[] = $argument->value;
            } elseif ($argument instanceof SelectArgument) {
                $values[] = $argument->value instanceof Select
                    ? '(' . $this->processSubSelect($argument->value) . ')'
                    : $this->processExpression($argument->value);
            }
        }

        return vsprintf($specification, $values);
    }

    /**
     * Process a subselect query.
     *
     * Subselects are rendered with embedded values (not parameterized) even when
     * the parent query uses prepared statement parameters. This is because:
     * 1. The old laminas-db behavior embedded subselect values
     * 2. It avoids parameter naming conflicts between nested queries
     */
    public function processSubSelect(Select $subselect): string
    {
        $this->subselectCount++;
        // Create a child builder WITHOUT driver/params to embed values directly
        $childBuilder = new self(
            $this->platform,
            null, // No driver = no parameterization
            null, // No params
            $this->paramPrefix . 'sub' . $this->subselectCount . '_'
        );

        return $subselect->prepareSqlString($childBuilder);
    }
}
