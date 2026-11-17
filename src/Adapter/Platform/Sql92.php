<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PhpDb\Adapter\Exception;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function addcslashes;

/**
 * @api
 */
class Sql92 extends AbstractPlatform
{
    final public const string PLATFORM_NAME = 'SQL92';

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
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new Platform($this);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When no driver is available to quote safely.
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        if (null === ($this->driver ?? null)) {
            throw Exception\RuntimeException::forVulnerablePlatformQuote(static::class, __METHOD__);
        }
        return '\'' . addcslashes($value, characters: "\x00\n\r\\'\"\x1a") . '\'';
    }
}
