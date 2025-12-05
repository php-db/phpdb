<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;

class Boolean extends Column
{
    protected string $type = 'BOOLEAN';

    protected bool $isNullable = false;

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setNullable(bool $nullable): static
    {
        return parent::setNullable(false);
    }
}
