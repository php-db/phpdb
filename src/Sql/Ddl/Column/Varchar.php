<?php

namespace PhpDb\Sql\Ddl\Column;

class Varchar extends AbstractLengthColumn
{
    /** @var string */
    protected $type = 'VARCHAR';
}
