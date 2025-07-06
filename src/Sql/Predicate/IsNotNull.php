<?php

namespace PhpDb\Sql\Predicate;

class IsNotNull extends IsNull
{
    /** @var string */
    protected $specification = '%1$s IS NOT NULL';
}
