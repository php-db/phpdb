<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use Exception;
use PhpDb\ResultSet\HydratingResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(HydratingResultSet::class, 'current')]
class HydratingResultSetIntegrationTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function aSecondBufferedPassHydratesTheSameData(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $hydratingRs->buffer();

        $first = $hydratingRs->current();
        $hydratingRs->rewind();

        static::assertEquals($first, $hydratingRs->current());
    }
}
