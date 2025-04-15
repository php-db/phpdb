<?php

namespace Laminas\Db\Sql;

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

    protected array $parameters = [];

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     */
    public function __construct(string $expression = '')
    {
        if ($expression !== '') {
            $this->setExpression($expression);
        }

        if (func_num_args() > 1) {
            $parameters = func_get_args();
            $parameters = array_slice($parameters, 1);
        } else {
            $parameters = null;
        }

        if ($parameters !== null) {
            call_user_func_array([$this, 'setParameters'], $parameters);
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
    public function setParameters(): self {
        if (func_num_args() > 0) {
            foreach (func_get_args() as $parameter) {
                if ($parameter !== null) {
                    $this->parameters[] = $parameter instanceof Argument ? $parameter : new Argument($parameter);
                }
            }
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
    public function getExpressionData(): array
    {
        $parameters      = $this->parameters;
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
            $values[] = $parameter;
        }
        return [
            [
                $expression,
                $values
            ],
        ];
    }
}
