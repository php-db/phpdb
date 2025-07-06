<?php

namespace PhpDb\Sql;

use function array_unique;
use function count;
use function is_array;
use function is_scalar;
use function preg_match_all;
use function str_ireplace;
use function str_replace;

class Expression extends AbstractExpression
{
    /**
     * @const
     */
    public const PLACEHOLDER = '?';

    protected string $expression = '';

    protected float|array|int|string|bool $parameters = [];

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     */
    public function __construct(string $expression = '', float|array|int|string|bool|null $parameters = null)
    {
        if ($expression !== '') {
            $this->setExpression($expression);
        }

        if ($parameters !== null) {
            $this->setParameters($parameters);
        }
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setExpression(string $expression): self
    {
        if ($expression === '') {
            throw new Exception\InvalidArgumentException('Supplied expression must not be an empty string.');
        }
        $this->expression = $expression;
        return $this;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setParameters(float|array|int|string|bool $parameters): self
    {
        if (! is_scalar($parameters) && ! is_array($parameters)) {
            throw new Exception\InvalidArgumentException('Expression parameters must be a scalar or array.');
        }
        $this->parameters = $parameters;
        return $this;
    }

    public function getParameters(): float|array|int|string|bool
    {
        return $this->parameters;
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function getExpressionData(): array
    {
        $parameters      = is_scalar($this->parameters) ? [$this->parameters] : $this->parameters;
        $parametersCount = count($parameters);
        $expression      = str_replace('%', '%%', $this->expression);

        if ($parametersCount === 0) {
            return [
                str_ireplace(self::PLACEHOLDER, '', $expression),
            ];
        }

        // assign locally, escaping % signs
        $expression = str_replace(self::PLACEHOLDER, '%s', $expression, $count);

        // test number of replacements without considering same variable begin used many times first, which is
        // faster, if the test fails then resort to regex which are slow and used rarely
        if ($count !== $parametersCount) {
            preg_match_all('/:\w*/', $expression, $matches);
            if ($parametersCount !== count(array_unique($matches[0]))) {
                throw new Exception\RuntimeException(
                    'The number of replacements in the expression does not match the number of parameters'
                );
            }
        }

        foreach ($parameters as $parameter) {
            [$values[], $types[]] = $this->normalizeArgument($parameter);
        }
        return [
            [
                $expression,
                $values,
                $types,
            ],
        ];
    }
}
