<?php

namespace PhpDb\Sql\Platform\SqlServer;

use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Select;

class SqlServer extends AbstractPlatform
{
    public function __construct(?SelectDecorator $selectDecorator = null)
    {
        $this->setTypeDecorator(Select::class, $selectDecorator ?: new SelectDecorator());
        $this->setTypeDecorator(CreateTable::class, new Ddl\CreateTableDecorator());
    }
}
