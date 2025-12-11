<?php

declare(strict_types=1);

namespace PhpDb\Sql;

class Where extends Predicate\Predicate
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
