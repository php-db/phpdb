<?php

namespace PhpDb\Sql;


use function array_slice;
use function array_unique;
use function count;
use function func_get_args;
use function func_num_args;
use function is_array;
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

    /** @var Argument[] */
    protected array $parameters = [];

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     */
    public function __construct(string $expression = '', null|bool|string|float|int|array|Argument|ExpressionInterface $parameters = [])
    {
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
    public function setParameters(null|bool|string|float|int|array|ExpressionInterface|Argument $parameters = []): self
    {
        if (func_num_args() > 1) {
            /**
             * @deprecated
             *
             * @todo Make notes in documentation
             */
            $parameters = func_get_args();
        }

        if (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        /** @var null|bool|string|float|int|array|ExpressionInterface|Argument $parameter */
        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof ArgumentType) {
                $parameter = new Argument($key, $parameter);
            } elseif (! $parameter instanceof Argument) {
                $parameter = new Argument($parameter);
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
     */
    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        $parameters      = $this->parameters;
        $parametersCount = count($parameters);
        $specification   = str_replace('%', '%%', $this->expression);

        if ($parametersCount === 0) {
            $specification = str_ireplace(self::PLACEHOLDER, '', $specification);
            return new ExpressionData($specification);
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

        return new ExpressionData($specification, $parameters);
    }
}
