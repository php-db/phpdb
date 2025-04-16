<?php

namespace Laminas\Db\Sql\Ddl\Column;

class Boolean extends Column
{
    /** @var string */
    protected string $type = 'BOOLEAN';

    /**
     * {@inheritDoc}
     */
    protected bool $isNullable = false;

    /**
     * {@inheritDoc}
     */
    public function setNullable($nullable)
    {
        return parent::setNullable(false);
    }
}
