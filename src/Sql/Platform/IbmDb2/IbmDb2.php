<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform\IbmDb2;

use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Select;

class IbmDb2 extends AbstractPlatform
{
    public function __construct(?SelectDecorator $selectDecorator = null)
    {
        $this->setTypeDecorator(Select::class, $selectDecorator ?: new SelectDecorator());
    }
}
