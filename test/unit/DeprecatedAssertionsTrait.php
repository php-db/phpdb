<?php

declare(strict_types=1);

namespace PhpDbTest;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\RequiresPhp;
use ReflectionException;
use ReflectionProperty;

trait DeprecatedAssertionsTrait
{
    /**
     * @throws ReflectionException
     */
    #[RequiresPhp('<= 8.4')]
    public static function assertAttributeEquals(
        mixed $expected,
        string $attribute,
        object $instance,
        string $message = ''
    ): void {
        $r = new ReflectionProperty($instance, $attribute);
        /** @noinspection PhpExpressionResultUnusedInspection */
        $r->setAccessible(true);
        Assert::assertEquals($expected, $r->getValue($instance), $message);
    }

    /**
     * @throws ReflectionException
     */
    #[RequiresPhp('<= 8.4')]
    public function readAttribute(object $instance, string $attribute): mixed
    {
        $r = new ReflectionProperty($instance, $attribute);
        /** @noinspection PhpExpressionResultUnusedInspection */
        $r->setAccessible(true);
        return $r->getValue($instance);
    }
}
