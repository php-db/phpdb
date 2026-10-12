<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Json extends Column
{
    protected string $type = 'JSON';
}
