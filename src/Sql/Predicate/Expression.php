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
use function strpos;
use function substr_replace;

final class Expression extends BaseExpression implements PredicateInterface
{
    /** @inheritDoc */
    #[Override]
    public function toSqlPart(string $q, PlatformInterface $platform): string
    {
        // Fast path: no parameters, return expression directly
        if ($this->parameters === []) {
            return $this->expression;
        }

        $sql = $this->expression;

        foreach ($this->parameters as $param) {
            $pos = strpos($sql, '?');
            if ($pos === false) {
                break;
            }

            if ($param instanceof Value) {
                $replacement = $platform->quoteTrustedValue($param->getValue());
            } elseif ($param instanceof Values) {
                $quoted = [];
                foreach ($param->getValue() as $v) {
                    $quoted[] = $platform->quoteTrustedValue($v);
                }
                $replacement = '(' . implode(', ', $quoted) . ')';
            } else {
                $replacement = $param->getSpecification();
            }

            $sql = substr_replace($sql, $replacement, $pos, 1);
        }

        return $sql;
    }
}
