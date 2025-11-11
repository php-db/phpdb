<?php

namespace PhpDb\Sql\Ddl\Constraint;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\ExpressionPart;

class Check extends AbstractConstraint
{
    /** @var string|ExpressionInterface */
    protected $expression;

    /**
     * {}
     */
    protected string $specification = 'CHECK (%s)';

    /**
     * @param string|ExpressionInterface $expression
     * @param  null|string $name
     */
    public function __construct($expression, $name)
    {
        parent::__construct(null, $name);

        $this->expression = $expression;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override] public function getExpressionData(): ExpressionData
    {
        $expressionPart = new ExpressionPart();

        if ($this->name !== '') {
            $expressionPart->addSpecification($this->namedSpecification);
            $expressionPart->addValue(new Argument($this->name, ArgumentType::Identifier));
        }

        if ($this->expression !== '') {
            $expressionPart->addSpecification($this->specification);
            $expressionPart->addValue(new Argument($this->expression, ArgumentType::Literal));
        }

        return new ExpressionData($expressionPart);
    }
}
