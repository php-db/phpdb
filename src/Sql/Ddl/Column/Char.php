<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class Char extends AbstractLengthColumn
{
    protected string $type = 'CHAR';
}
