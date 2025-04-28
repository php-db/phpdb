<?php

namespace Laminas\Db\Adapter\Platform;

use Override;

use function addcslashes;
use function trigger_error;

class Sql92 extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    #[Override] public function getName()
    {
        return 'SQL92';
    }

    /**
     * {@inheritDoc}
     */
    #[Override] public function quoteValue($value): string
    {
        trigger_error(
            'Attempting to quote a value without specific driver level support'
            . ' can introduce security vulnerabilities in a production environment.'
        );
        return '\'' . addcslashes($value ?? '', "\x00\n\r\\'\"\x1a") . '\'';
    }
}
