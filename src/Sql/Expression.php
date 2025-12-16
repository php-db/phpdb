<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;

use function array_unique;
use function count;
use function is_array;
use function preg_match_all;
use function str_replace;

class Expression extends AbstractExpression
{
    /**
     * @const
     */
    final public const PLACEHOLDER = '?';

    protected string $expression = '';

    /** @var ArgumentInterface[] */
    protected array $parameters = [];

    public function __construct(
        string $expression = '',
        null|bool|string|float|int|array|ArgumentInterface|ExpressionInterface $parameters = null
    ) {
        if ($expression !== '') {
            $this->expression = $expression;
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
     * String representation for debugging and test output.
     */
    public function __toString(): string
    {
        return $this->expression;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setParameters(
        null|bool|string|float|int|array|ExpressionInterface|ArgumentInterface $parameters = []
    ): self {
        if (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        foreach ($parameters as $parameter) {
            if (is_array($parameter)) {
                $parameter = new Values($parameter);
            } elseif ($parameter instanceof ExpressionInterface) {
                $parameter = new SelectArgument($parameter);
            } elseif (! $parameter instanceof ArgumentInterface) {
                $parameter = new Value($parameter);
            }

            $this->parameters[] = $parameter;
        }

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws Exception\RuntimeException
     * @inheritDoc
     */
    #[Override]
    public function getExpressionData(): array
    {
        $parameters      = $this->parameters;
        $parametersCount = count($parameters);

        // Fast path: no parameters - return expression as-is (no string manipulation needed)
        if ($parametersCount === 0) {
            return [
                'spec'   => $this->expression,
                'values' => [],
            ];
        }

        // Escape % for vsprintf, then replace ? placeholders with %s
        $specification = str_replace(['%', self::PLACEHOLDER], ['%%', '%s'], $this->expression, $count);

        if ($count !== $parametersCount) {
            preg_match_all('/:\w*/', $specification, $matches);
            if ($parametersCount !== count(array_unique($matches[0]))) {
                throw new Exception\RuntimeException(
                    'The number of replacements in the expression does not match the number of parameters'
                );
            }
        }

        return [
            'spec'   => $specification,
            'values' => $parameters,
        ];
    }
}
