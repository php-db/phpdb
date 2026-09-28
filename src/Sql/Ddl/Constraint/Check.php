<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionInterface;

use function implode;
use function sprintf;

class Check extends AbstractConstraint
{
    protected string|ExpressionInterface $expression;

    protected string $specification = 'CHECK (%s)';

    /**
     * A string expression is rendered verbatim as a literal SQL fragment. An ExpressionInterface
     * expression is rendered through the platform, so its identifiers and values are quoted.
     *
     * @throws InvalidArgumentException When the expression is an empty string.
     */
    public function __construct(string|ExpressionInterface $expression, ?string $name = null)
    {
        if ('' === $expression) {
            throw new InvalidArgumentException('Check constraint expression must not be an empty string.');
        }

        parent::__construct(null, $name);

        $this->expression = $expression;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        $checkData = $this->expression instanceof ExpressionInterface
            ? $this->expression->getExpressionData()
            : ['spec' => '%s', 'values' => [new Literal($this->expression)]];

        $specParts = [];
        $values    = [];

        if ('' !== $this->name) {
            $specParts[] = $this->namedSpecification;
            $values[]    = new Identifier($this->name);
        }

        $specParts[] = sprintf($this->specification, $checkData['spec']);
        foreach ($checkData['values'] as $value) {
            $values[] = $value;
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $values,
        ];
    }
}
