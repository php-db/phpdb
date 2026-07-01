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
    public function testConstructorStoresMessageAndParameters(): void
    {
        $exception = new InvalidConnectionParametersException('msg', ['host', 'port']);

        self::assertSame('msg', $exception->getMessage());
    }
}
