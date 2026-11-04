<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\Adapter\Platform\Sql92;

/**
 * Backtick-quoting platform whose fragment pattern treats `-` as a word character, as phpdb-mysql does
 */
final class TrustingHyphenWordPlatform extends Sql92
{
    /** @var array{string, string} */
    protected array $quoteIdentifier = ['`', '`'];

    protected string $quoteIdentifierTo = '``';

    protected string $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';

    #[Override]
    public function quoteValue(string $value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
