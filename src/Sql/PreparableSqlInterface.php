<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\StatementContainerInterface;

interface PreparableSqlInterface
{
    // Processing markers for deferred quoting - enables single-pass assembly
    public const P_LQUOTE = '{"';    // Left identifier quote marker
    public const P_RQUOTE = '"}';    // Right identifier quote marker
    public const P_VALUE  = '{?}';   // Value placeholder marker
    public const P_SELECT = '{SQL}'; // Subquery placeholder marker

    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface;
}
