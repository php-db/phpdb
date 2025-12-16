<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;

class Literal implements ExpressionInterface
{
    public function __construct(protected string $literal = '')
    {
        $this->literal = $literal;
    }

    public function setLiteral(string $literal): static
    {
        $this->literal = $literal;
        return $this;
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        return [
            'spec'   => $this->literal,
            'values' => [],
        ];
    }
}
