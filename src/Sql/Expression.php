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

/**
 * @api
 *
 * @psalm-type ExpressionParameter = bool|string|float|int|null|list<bool|string|float|int|null>|ExpressionInterface|ArgumentInterface
 */
class Expression extends AbstractExpression
{
    /**
     * @const
     */
    final public const string PLACEHOLDER = '?';

    protected string $expression = '';

    /** @var list<ArgumentInterface> */
    protected array $parameters = [];

    /**
     * @todo Update documentation to show how parameters can be specifically typed
     *
     * @param ExpressionParameter|array<array-key, ExpressionParameter> $parameters
     */
    public function __construct(
        string $expression = '',
        bool|string|float|int|array|ArgumentInterface|ExpressionInterface|null $parameters = [],
    ) {
        if ('' !== $expression) {
            $this->setExpression($expression);
        }

        if (func_num_args() > 2) {
            /**
             * Passing parameters as separate arguments is deprecated
             *
             * @var list<ExpressionParameter> $parameters
             */
            $parameters = array_slice(func_get_args(), offset: 1);
        }

        $this->setParameters($parameters);
    }

    public function getExpression(): string
    {
        return $this->expression;
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
        $specification   = str_replace('%', replace: '%%', subject: $this->expression);

        if (0 === $parametersCount) {
            return [
                'spec'   => $specification,
                'values' => [],
            ];
        }

        // assign locally, escaping % signs
        $count         = 0;
        $specification = str_replace(self::PLACEHOLDER, replace: '%s', subject: $specification, count: $count);

        // test number of replacements without considering same variable begin used many times first, which is
        // faster, if the test fails then resort to regex which are slow and used rarely
        if ($count !== $parametersCount) {
            $matches = [];
            preg_match_all('/:\w*/', $specification, $matches);
            if (count(array_unique($matches[0] ?? [])) !== $parametersCount) {
                throw Exception\RuntimeException::forReplacementMismatch();
            }
        }

        return [
            'spec'   => $specification,
            'values' => $parameters,
        ];
    }

    /** @return list<ArgumentInterface> */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function setExpression(string $expression): self
    {
        if ('' === $expression) {
            throw Exception\InvalidArgumentException::forEmptyExpression();
        }

        $this->expression = $expression;
        return $this;
    }

    /**
     * @param ExpressionParameter|array<array-key, ExpressionParameter> $parameters
     * @throws Exception\InvalidArgumentException
     */
    public function setParameters(
        bool|string|float|int|array|ExpressionInterface|ArgumentInterface|null $parameters = [],
    ): self {
        if (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        foreach ($parameters as $parameter) {
            $this->parameters[] = match (true) {
                is_array($parameter) => new Values($parameter),
                $parameter instanceof ExpressionInterface => new SelectArgument($parameter),
                $parameter instanceof ArgumentInterface => $parameter,
                default => new Value($parameter),
            };
        }

        return $this;
    }

    /**
     * Expression renders the string given to setExpression(), so a
     * specification override would be silently ignored. Use setExpression().
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function setSpecification(string $specification): static
    {
        throw Exception\RuntimeException::forUnsupportedSpecificationOverride(static::class);
    }
}
