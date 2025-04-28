<?php

namespace Laminas\Db\Sql\Predicate;

final class NotIn extends In
{
    protected string $specification = '%s NOT IN %s';
}
