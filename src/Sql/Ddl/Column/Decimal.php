<?php

namespace PhpDb\Sql\Ddl\Column;

class Decimal extends AbstractPrecisionColumn
{
    /** @var string */
    protected $type = 'DECIMAL';
}
