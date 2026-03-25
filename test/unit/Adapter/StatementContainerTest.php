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
    public function testConstructorWithSqlSetsSql(): void
    {
        $container = new StatementContainer('SELECT 1');

        self::assertSame('SELECT 1', $container->getSql());
    }

    public function testConstructorWithoutSqlDoesNotSetSql(): void
    {
        $container = new StatementContainer();

        self::assertSame('', $container->getSql());
    }

    public function testSetAndGetSql(): void
    {
        $container = new StatementContainer();

        $result = $container->setSql('test');

        self::assertSame($container, $result);
        self::assertSame('test', $container->getSql());
    }

    public function testSetAndGetParameterContainer(): void
    {
        $container          = new StatementContainer();
        $parameterContainer = new ParameterContainer(['a' => 1]);

        $result = $container->setParameterContainer($parameterContainer);

        self::assertSame($container, $result);
        self::assertSame($parameterContainer, $container->getParameterContainer());
    }
}
