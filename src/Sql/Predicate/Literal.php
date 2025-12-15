<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Literal as BaseLiteral;

final class Literal extends BaseLiteral implements PredicateInterface
{
    protected string $combination = 'AND';

    /** @inheritDoc */
    #[Override]
    public function toSqlPart(string $q, PlatformInterface $platform): string
    {
        return $this->literal;
    }

    /** @inheritDoc */
    #[Override]
    public function setCombination(string $combination): static
    {
        $this->combination = $combination;
        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function getCombination(): string
    {
        return $this->combination;
    }
}
