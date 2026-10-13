<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Sql92;

/**
 * SQL-92 platform whose identifier fragment pattern treats `-` as a word character, as phpdb-mysql does
 */
final class HyphenWordSql92Platform extends Sql92
{
    protected string $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';
}
