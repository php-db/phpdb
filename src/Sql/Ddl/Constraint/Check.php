<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ExpressionInterface;

use function implode;

class Check extends AbstractConstraint
{
    protected string|ExpressionInterface $expression;

    protected string $specification = 'CHECK (%s)';

    /**
     * @param string|ExpressionInterface $expression
     */
    public function __construct($expression, ?string $name)
    {
        parent::__construct(null, $name);

        $this->expression = $expression;
    }

    /** @inheritDoc */
    #[Override] public function getExpressionData(): array
    {
        $specParts = [];
        $values    = [];

        if ($this->name !== '') {
            $specParts[] = $this->namedSpecification;
            $values[]    = new Identifier($this->name);
        }

        if ($this->expression !== '') {
            $specParts[] = $this->specification;
            $values[]    = new Literal($this->expression);
        }

        return [
            'spec'   => implode(' ', $specParts),
            'values' => $values,
        ];
    }
}
