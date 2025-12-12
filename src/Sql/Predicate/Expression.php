<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\Expression as BaseExpression;
use PhpDb\Sql\PreparableSqlInterface;

use function implode;
use function preg_replace;

final class Expression extends BaseExpression implements PredicateInterface
{
    /** @inheritDoc */
    #[Override]
    public function toSqlPart(string $q, PlatformInterface $platform): string
    {
        $sql = $this->expression;

        foreach ($this->parameters as $param) {
            if ($param instanceof Value) {
                $sql = preg_replace('/\?/', $platform->quoteTrustedValue($param->getValue()), $sql, 1);
            } elseif ($param instanceof Values) {
                $quoted = [];
                foreach ($param->getValue() as $v) {
                    $quoted[] = $platform->quoteTrustedValue($v);
                }
                $sql = preg_replace('/\?/', '(' . implode(', ', $quoted) . ')', $sql, 1);
            } else {
                $sql = preg_replace('/\?/', $param->getSpecification(), $sql, 1);
            }
        }

        return $sql;
    }
}
