<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

final class UniqueKey extends AbstractConstraint
{
    protected string $specification = 'UNIQUE';
}
