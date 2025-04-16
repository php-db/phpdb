<?php

namespace Laminas\Db\Sql\Ddl\Column;

class Varchar extends AbstractLengthColumn
{
    /** @var string */
    protected string $type = 'VARCHAR';
}
