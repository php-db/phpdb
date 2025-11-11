<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class Text extends AbstractLengthColumn
{
    protected string $specification = '%s %s';

    protected string $type = 'TEXT';
}
