<?php

namespace Laminas\Db\Sql\Predicate;

class IsNotNull extends IsNull
{
    protected string $specification = '%1$s IS NOT NULL';
}
