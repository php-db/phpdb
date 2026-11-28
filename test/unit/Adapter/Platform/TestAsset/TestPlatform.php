<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Platform\TestAsset;

use Override;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\StatementFactoryInterface;

final class TestPlatform extends AbstractPlatform
{
    /** @param array{string, string} $quoteIdentifier */
    public function __construct(
        protected array $quoteIdentifier = ['"', '"'],
    ) {}

    #[Override]
    public function getName(): string
    {
        return 'Test';
    }

    #[Override]
    public function getStatementFactory(): StatementFactoryInterface
    {
        return new Platform();
    }

    /**
     * A bare platform has no driver to quote values with, which is what the exception reports.
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        throw Exception\RuntimeException::forVulnerablePlatformQuote(static::class, __METHOD__);
    }
}
