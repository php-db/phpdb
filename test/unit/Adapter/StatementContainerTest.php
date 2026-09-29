<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(StatementContainer::class, '__construct')]
#[CoversMethod(StatementContainer::class, 'setSql')]
#[CoversMethod(StatementContainer::class, 'getSql')]
#[CoversMethod(StatementContainer::class, 'setParameterContainer')]
#[CoversMethod(StatementContainer::class, 'getParameterContainer')]
final class StatementContainerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithoutSqlDoesNotSetSql(): void
    {
        $container = new StatementContainer();

        static::assertSame('', $container->getSql());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithSqlSetsSql(): void
    {
        $container = new StatementContainer('SELECT 1');

        static::assertSame('SELECT 1', $container->getSql());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setAndGetParameterContainer(): void
    {
        $container          = new StatementContainer();
        $parameterContainer = new ParameterContainer(['a' => 1]);

        $result = $container->setParameterContainer($parameterContainer);

        static::assertSame($container, $result);
        static::assertSame($parameterContainer, $container->getParameterContainer());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setAndGetSql(): void
    {
        $container = new StatementContainer();

        $result = $container->setSql('test');

        static::assertSame($container, $result);
        static::assertSame('test', $container->getSql());
    }
}
