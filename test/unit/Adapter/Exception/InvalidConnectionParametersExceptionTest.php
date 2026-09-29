<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Exception;

use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(InvalidConnectionParametersException::class, '__construct')]
final class InvalidConnectionParametersExceptionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorStoresMessageAndParameters(): void
    {
        $exception = new InvalidConnectionParametersException('msg', ['host', 'port']);

        static::assertSame('msg', $exception->getMessage());
    }
}
