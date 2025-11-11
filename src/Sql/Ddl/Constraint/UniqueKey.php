<?php

namespace PhpDb\Sql\Ddl\Constraint;

class UniqueKey extends AbstractConstraint
{
    protected string $specification = 'UNIQUE';
}
