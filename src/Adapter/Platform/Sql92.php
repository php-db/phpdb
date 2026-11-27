<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PhpDb\Adapter\Exception;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\StatementFactoryInterface;

use function addcslashes;

/**
 * Standard SQL conformance platform.
 *
 * The test suite renders statements through it, and extends it to specialize quoting per dialect,
 * which is why it is not final. Nothing in the library selects it and no statement falls back to it.
 *
 * @internal
 *
 * @mago-expect analysis:class-must-be-final
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
    public function getStatementFactory(): StatementFactoryInterface
    {
        return new Platform();
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
