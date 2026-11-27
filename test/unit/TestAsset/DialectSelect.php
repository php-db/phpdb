<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\Select;

/**
 * A Select whose dialect renders the limit clause with a different specification, so a test can
 * prove which class rendered a statement.
 */
final class DialectSelect extends Select
{
    final public const string LIMIT_SPECIFICATION = 'DIALECT_LIMIT %1$s';

    protected function prepareSpecifications(): void
    {
        if (null !== $this->limit) {
            $this->setSpecification(self::LIMIT, self::LIMIT_SPECIFICATION);
        }
    }
}
