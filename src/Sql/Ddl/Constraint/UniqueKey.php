<?php

namespace PhpDb\Sql\Ddl\Constraint;

class UniqueKey extends AbstractConstraint
{
    /** @var string */
    protected $specification = 'UNIQUE';
}
