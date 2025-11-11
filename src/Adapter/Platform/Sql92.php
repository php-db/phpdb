<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function addcslashes;
use function trigger_error;

class Sql92 extends AbstractPlatform
{
    public final const PLATFORM_NAME = 'SQL92';

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getName(): string
    {
        return self::PLATFORM_NAME;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue($value): string
    {
        trigger_error(
            'Attempting to quote a value without specific driver level support'
            . ' can introduce security vulnerabilities in a production environment.'
        );
        return '\'' . addcslashes($value ?? '', "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new Platform($this);
    }
}
