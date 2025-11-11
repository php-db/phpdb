<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

class UniqueKey extends AbstractConstraint
{
    protected string $specification = 'UNIQUE';
}
