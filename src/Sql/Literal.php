<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;

use function str_replace;

class Literal implements ExpressionInterface
{
    public function __construct(
        protected string $literal = '',
    ) {
        $this->literal = $literal;
    }

    /** @inheritDoc */
    #[Override]
    public function getExpressionData(): array
    {
        return [
            'spec'   => str_replace('%', replace: '%%', subject: $this->literal),
            'values' => [],
        ];
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    public function setLiteral(string $literal): static
    {
        $this->literal = $literal;
        return $this;
    }
}
