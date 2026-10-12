<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

/**
 * @api
 */
class Decimal extends AbstractPrecisionColumn
{
    protected string $type = 'DECIMAL';
}
