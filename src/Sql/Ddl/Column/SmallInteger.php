<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class SmallInteger extends Integer
{
    protected string $type = 'SMALLINT';
}
