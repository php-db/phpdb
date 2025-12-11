<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\StatementContainerInterface;

interface PreparableSqlInterface
{
    public const P_LQUOTE = '{"';
    public const P_RQUOTE = '"}';
    public const P_VALUE  = '{?}';
    public const P_SELECT = '{SQL}';

    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface;
}
