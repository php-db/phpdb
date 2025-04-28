<?php

namespace Laminas\Db\Sql\Predicate;

final class NotLike extends Like
{
    protected string $specification = '%1$s NOT LIKE %2$s';
}
