<?php

namespace Laminas\Db\Sql\Ddl\Column;

final class Decimal extends AbstractPrecisionColumn
{
    protected string $type = 'DECIMAL';
}
