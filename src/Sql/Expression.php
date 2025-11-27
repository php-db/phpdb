<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Argument\ArgumentType as NewArgumentType;
use PhpDb\Sql\ArgumentType as OldArgumentType;

use function array_slice;
use function array_unique;
use function count;
use function current;
use function func_get_args;
use function func_num_args;
use function is_array;
use function key;
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
    public function setParameters(null|bool|string|float|int|array|ExpressionInterface|ArgumentInterface $parameters = []): self
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

        /** @var null|bool|string|float|int|array|ExpressionInterface|ArgumentInterface $parameter */
        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof ArgumentInterface) {
                // Already an ArgumentInterface, use as-is
            } elseif ($parameter instanceof NewArgumentType || $parameter instanceof OldArgumentType) {
                // Legacy support: ['name' => ArgumentType::Identifier] syntax
                // Handle both old and new ArgumentType enums
                $parameter = match (true) {
                    $parameter === NewArgumentType::Identifier || $parameter === OldArgumentType::Identifier => Argument::identifier($key),
                    $parameter === NewArgumentType::Literal || $parameter === OldArgumentType::Literal => Argument::literal($key),
                    default => Argument::value($key),
                };
            } elseif (is_array($parameter)) {
                // Legacy support: [['name' => ArgumentType::Identifier]] syntax
                // Check if this is a single-element array with ArgumentType as value
                if (count($parameter) === 1) {
                    $arrayKey = key($parameter);
                    $arrayValue = current($parameter);
                    if ($arrayValue instanceof NewArgumentType || $arrayValue instanceof OldArgumentType) {
                        $parameter = match (true) {
                            $arrayValue === NewArgumentType::Identifier || $arrayValue === OldArgumentType::Identifier => Argument::identifier($arrayKey),
                            $arrayValue === NewArgumentType::Literal || $arrayValue === OldArgumentType::Literal => Argument::literal($arrayKey),
                            default => Argument::value($arrayKey),
                        };
                    } else {
                        // Array of values
                        $parameter = Argument::values($parameter);
                    }
                } else {
                    // Array of values
                    $parameter = Argument::values($parameter);
                }
            } elseif ($parameter instanceof ExpressionInterface) {
                // Handle expressions/subqueries
                $parameter = Argument::select($parameter);
            } else {
                $parameter = Argument::value($parameter);
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
    #[Override]
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
