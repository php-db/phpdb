<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\Expression as BaseExpression;
use PhpDb\Sql\PreparableSqlInterface;

use function preg_replace;

class Expression extends BaseExpression implements PredicateInterface
{
    /** @inheritDoc */
    #[Override]
    public function toSqlPart(array &$values): string
    {
        $sql = $this->expression;

        // Replace ? placeholders with markers and collect values
        foreach ($this->parameters as $param) {
            if ($param instanceof Value) {
                $values[] = $param->getValue();
                $sql      = preg_replace('/\?/', PreparableSqlInterface::P_VALUE, $sql, 1);
            } elseif ($param instanceof Values) {
                foreach ($param->getValue() as $v) {
                    $values[] = $v;
                }
                $sql = preg_replace('/\?/', $param->getSpecification(), $sql, 1);
            } else {
                // For other argument types, use getSpecification()
                $sql = preg_replace('/\?/', $param->getSpecification(), $sql, 1);
            }
        }

        return $sql;
    }
}
