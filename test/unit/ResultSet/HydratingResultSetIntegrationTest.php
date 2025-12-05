<?php

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use Exception;
use PhpDb\ResultSet\HydratingResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(HydratingResultSet::class, 'current')]
class HydratingResultSetIntegrationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCurrentWillReturnBufferedRow(): void
    {
        $hydratingRs = new HydratingResultSet();
        $hydratingRs->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $hydratingRs->buffer();

        // Get current object and rewind to verify same buffered object is returned
        $obj1 = $hydratingRs->current();
        $hydratingRs->rewind();
        $obj2 = $hydratingRs->current();
        self::assertSame($obj1, $obj2);
    }
}
