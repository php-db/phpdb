<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;

use function str_replace;

class Literal implements ExpressionInterface
{
    protected string $literal = '';

    public function __construct(string $literal = '')
    {
        $this->literal = $literal;
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setLiteral(string $literal): static
    {
        $this->literal = $literal;
        return $this;
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    #[Override]
    public function getExpressionData(): ExpressionData
    {
        return new ExpressionData(str_replace('%', '%%', $this->literal));
    }
}
