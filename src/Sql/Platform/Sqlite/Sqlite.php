<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform\Sqlite;

use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Select;

class Sqlite extends AbstractPlatform
{
    /**
     * Constructor
     *
     * Registers the type decorator.
     */
    public function __construct()
    {
        $this->setTypeDecorator(Select::class, new SelectDecorator());
    }
}
