<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Varchar extends AbstractLengthColumn
{
    protected bool $lengthRequired = true;

    protected string $type = 'VARCHAR';
}
