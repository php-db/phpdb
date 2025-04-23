<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\ExpressionData;
use Laminas\Db\Sql\ExpressionInterface;

use Laminas\Db\Sql\ExpressionPart;

use function array_unshift;

class Check extends AbstractConstraint
{
    /** @var string|ExpressionInterface */
    protected $expression;

    /**
     * {@inheritDoc}
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
    public function getExpressionData(): ExpressionData
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
