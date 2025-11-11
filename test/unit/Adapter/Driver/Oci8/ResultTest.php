<?php

namespace PhpDbTest\Adapter\Driver\Oci8;

use PhpDb\Adapter\Driver\Oci8\Result;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Result::class, 'getResource')]
#[CoversMethod(Result::class, 'buffer')]
#[CoversMethod(Result::class, 'isBuffered')]
#[CoversMethod(Result::class, 'getGeneratedValue')]
#[CoversMethod(Result::class, 'key')]
#[CoversMethod(Result::class, 'next')]
#[CoversMethod(Result::class, 'rewind')]
#[Group('result-oci8')]
class ResultTest extends TestCase
{
    public function testGetResource(): void
    {
        $result = new Result();
        self::assertNull($result->getResource());
    }

    public function testBuffer(): void
    {
        $result = new Result();
        self::assertNull($result->buffer());
    }

    public function testIsBuffered(): void
    {
        $result = new Result();
        self::assertFalse($result->isBuffered());
    }

    public function testGetGeneratedValue(): void
    {
        $result = new Result();
        self::assertNull($result->getGeneratedValue());
    }

    public function testKey(): void
    {
        $result = new Result();
        self::assertEquals(0, $result->key());
    }

    public function testNext(): void
    {
        $mockResult = $this->getMockBuilder(Result::class)
            ->onlyMethods(['loadData'])
            ->getMock();
        $mockResult->expects($this->any())
            ->method('loadData')
            ->willReturn(null);
        self::assertNull($mockResult->next());
    }

    public function testRewind(): void
    {
        $result = new Result();
        self::assertNull($result->rewind());
    }
}
