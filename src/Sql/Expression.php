<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;

use function array_slice;
use function array_unique;
use function count;
use function func_get_args;
use function func_num_args;
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

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     */
    public function __construct(
        string $expression = '',
        null|bool|string|float|int|array|ArgumentInterface|ExpressionInterface $parameters = []
    ) {
        if ($expression !== '') {
            $this->setExpression($expression);
        }

        if (func_num_args() > 2) {
            /**
             * @deprecated
             *
             * @todo Make notes in documentation
             */
            $parameters = array_slice(func_get_args(), 1);
        }

        $this->setParameters($parameters);
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
        $specification   = str_replace('%', '%%', $this->expression);

        if ($parametersCount === 0) {
            return [
                'spec'   => $specification,
                'values' => [],
            ];
        }

        // assign locally, escaping % signs
        $specification = str_replace(self::PLACEHOLDER, '%s', $specification, $count);

        // test number of replacements without considering same variable begin used many times first, which is
        // faster, if the test fails then resort to regex which are slow and used rarely
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
