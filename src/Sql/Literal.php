<?php

namespace PhpDb\Sql;


use function str_replace;

class Literal implements ExpressionInterface
{
    /** @var string */
    protected $literal = '';

    /**
     * @param string $literal
     */
    public function __construct($literal = '')
    {
        $this->literal = $literal;
    }

    /**
     * @param string $literal
     * @return $this Provides a fluent interface
     */
    public function setLiteral($literal)
    {
        $this->literal = $literal;
        return $this;
    }

    /**
     * @return string
     */
    public function getLiteral()
    {
        return $this->literal;
    }

    #[\Override]
    public function getExpressionData(): ExpressionData
    {
        return new ExpressionData(str_replace('%', '%%', $this->literal));
    }
}
