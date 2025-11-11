<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class Binary extends AbstractLengthColumn
{
    protected string $type = 'BINARY';
}
