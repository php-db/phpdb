<?php

namespace PhpDb\Sql\Ddl\Column;

class Blob extends AbstractLengthColumn
{
    /** @var string Change type to blob */
    protected $type = 'BLOB';
}
