<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Source\TestAsset;

use PhpDb\Metadata\Source\AbstractSource;

/**
 * A concrete AbstractSource that deliberately leaves column data
 * unpopulated, simulating a subclass with incomplete loadColumnData.
 */
class IncompleteSource extends AbstractSource
{
    protected function loadSchemaData(): void
    {
    }

    protected function loadColumnData(string $table, string $schema): void
    {
    }
}
