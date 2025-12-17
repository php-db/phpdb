<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function addcslashes;

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
    public function quoteValue(string $value): string
    {
        if (! isset($this->driver)) {
            throw VunerablePlatformQuoteException::forPlatformAndMethod(
                static::class,
                __METHOD__
            );
        }
        return '\'' . addcslashes($value, "\x00\n\r\\'\"\x1a") . '\'';
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
