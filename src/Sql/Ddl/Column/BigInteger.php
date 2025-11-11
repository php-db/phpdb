<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

class BigInteger extends Integer
{
    protected string $type = 'BIGINT';
}
