<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\Literal as BaseLiteral;

class Literal extends BaseLiteral implements PredicateInterface
{
    /** @inheritDoc */
    #[Override]
    public function toSqlPart(array &$values): string
    {
        return $this->literal;
    }
}
