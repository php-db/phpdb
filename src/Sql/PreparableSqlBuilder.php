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
    /** Quote character for identifiers (e.g., ` or ") */
    public readonly string $q;

    private int $paramIndex     = 1;
    private int $subselectCount = 0;

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
        return $child;
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
                $values[] = $this->bindValue($argument->getValue());
            } elseif ($argument instanceof Values) {
                $values[] = '(' . $this->bindValues($argument->getValue()) . ')';
            } elseif ($argument instanceof Identifier) {
                $values[] = $this->quoteIdentifierInFragment($argument->getValue());
            } elseif ($argument instanceof Literal) {
                $values[] = $argument->getValue();
            } elseif ($argument instanceof SelectArgument) {
                $select   = $argument->getValue();
                $values[] = $select instanceof Select
                    ? '(' . $this->processSubSelect($select) . ')'
                    : $this->processExpression($select);
            }
        }

        return vsprintf($specification, $values);
    }

    /**
     * Process a subselect query.
     */
    public function processSubSelect(Select $subselect): string
    {
        $this->subselectCount++;
        $childBuilder = $this->withPrefix('sub' . $this->subselectCount . '_');

        return $subselect->prepareSqlString($childBuilder);
    }
}
