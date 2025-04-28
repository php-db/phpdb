<?php

namespace Laminas\Db\Sql\Ddl\Constraint;

final class PrimaryKey extends AbstractConstraint
{
    protected string $specification = 'PRIMARY KEY';
}
