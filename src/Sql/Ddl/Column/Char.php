<?php

namespace PhpDb\Sql\Ddl\Column;

class Char extends AbstractLengthColumn
{
    /** @var string */
    protected $type = 'CHAR';
}
