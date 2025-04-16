<?php

namespace Laminas\Db\Sql\Ddl\Column;

class Decimal extends AbstractPrecisionColumn
{
    /** @var string */
    protected string $type = 'DECIMAL';
}
