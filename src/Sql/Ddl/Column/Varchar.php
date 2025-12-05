<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class Varchar extends AbstractLengthColumn
{
    protected string $type = 'VARCHAR';
}
