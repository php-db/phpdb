<?php

namespace LaminasTest\Db;

use PHPUnit\Framework\Assert;
use ReflectionException;
use ReflectionProperty;

trait DeprecatedAssertionsTrait
{
    /**
     * @param mixed  $expected
     * @throws ReflectionException
     */
    public static function assertAttributeEquals(
        mixed $expected,
        string $attribute,
        object $instance,
        string $message = ''
    ): void {
        $r = new ReflectionProperty($instance, $attribute);
        /** @psalm-suppress UnusedMethodCall */
        $r->setAccessible(true);
        Assert::assertEquals($expected, $r->getValue($instance), $message);
    }

    /**
     * @throws ReflectionException
     * @return mixed
     */
    public function readAttribute(object $instance, string $attribute): mixed
    {
        $r = new ReflectionProperty($instance, $attribute);
        /** @psalm-suppress UnusedMethodCall */
        $r->setAccessible(true);
        return $r->getValue($instance);
    }
}
