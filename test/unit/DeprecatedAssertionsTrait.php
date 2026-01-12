<?php

declare(strict_types=1);

namespace PhpDbTest;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use ReflectionException;
use ReflectionProperty;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
trait DeprecatedAssertionsTrait
{
    /**
     * @throws ReflectionException
     */
    public static function assertAttributeEquals(
        mixed $expected,
        string $attribute,
        object $instance,
        string $message = ''
    ): void {
        $r = new ReflectionProperty($instance, $attribute);
        Assert::assertEquals($expected, $r->getValue($instance), $message);
    }

    /**
     * @throws ReflectionException
     */
    public function readAttribute(object $instance, string $attribute): mixed
    {
        $r = new ReflectionProperty($instance, $attribute);
        return $r->getValue($instance);
    }
}
