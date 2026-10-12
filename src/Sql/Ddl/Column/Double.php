<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Double extends AbstractPrecisionColumn
{
    protected string $type = 'DOUBLE';
}
