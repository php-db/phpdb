<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

class UniqueKey extends AbstractConstraint
{
    protected string $specification = 'UNIQUE';
}
