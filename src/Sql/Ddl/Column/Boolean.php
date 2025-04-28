<?php

namespace Laminas\Db\Sql\Ddl\Column;

use Override;

class Boolean extends Column
{
    protected string $type = 'BOOLEAN';

    /**
     * {}
     */
    protected bool $isNullable = false;

    /**
     * {@inheritDoc}
     */
    #[Override] public function setNullable($nullable)
    {
        return parent::setNullable(false);
    }
}
