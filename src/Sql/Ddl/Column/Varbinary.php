<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class Varbinary extends AbstractLengthColumn
{
    protected string $type = 'VARBINARY';
}
