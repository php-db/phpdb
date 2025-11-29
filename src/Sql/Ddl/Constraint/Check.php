<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\ExpressionPart;

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

    /**
     * {@inheritDoc}
     */
    #[Override] public function getExpressionData(): ExpressionData
    {
        $expressionPart = new ExpressionPart();

        if ($this->name !== '') {
            $expressionPart->addSpecification($this->namedSpecification);
            $expressionPart->addValue(new Identifier($this->name));
        }

        if ($this->expression !== '') {
            $expressionPart->addSpecification($this->specification);
            $expressionPart->addValue(new Literal($this->expression));
        }

        return new ExpressionData($expressionPart);
    }
}
