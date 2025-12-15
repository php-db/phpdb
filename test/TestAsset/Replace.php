<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql\AbstractInsert;

/**
 * Test asset for REPLACE INTO functionality.
 */
final class Replace extends AbstractInsert
{
    protected function getInsertKeyword(): string
    {
        return 'REPLACE';
    }
}
