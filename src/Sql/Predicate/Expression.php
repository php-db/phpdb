<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\Expression as BaseExpression;
use PhpDb\Sql\PreparableSqlBuilder;

use function strpos;
use function substr_replace;

final class Expression extends BaseExpression implements PredicateInterface
{
    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        $sql = $builder->quoteIdentifierInFragment($this->expression);

        if ($this->parameters !== []) {
            foreach ($this->parameters as $param) {
                $pos = strpos($sql, '?');
                if ($pos === false) {
                    break;
                }

                $sql = substr_replace($sql, $param->toSql($builder), $pos, 1);
            }
        }

        return $sql;
    }
}
