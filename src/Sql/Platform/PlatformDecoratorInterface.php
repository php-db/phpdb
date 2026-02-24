<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

interface PlatformDecoratorInterface
{
    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject
    ): PlatformDecoratorInterface;
}
