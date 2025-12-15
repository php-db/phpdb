<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\Predicate\Predicate;

class WhereClause extends Predicate implements ClauseInterface
{
    protected string $prefix = 'WHERE';

    protected bool $emptyAllowed = false;

    public function setEmptyAllowed(bool $allowed = true): static
    {
        $this->emptyAllowed = $allowed;
        return $this;
    }

    public function isEmptyAllowed(): bool
    {
        return $this->emptyAllowed;
    }
}