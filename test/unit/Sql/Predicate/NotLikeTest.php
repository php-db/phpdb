<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\NotLike;
use PHPUnit\Framework\TestCase;

final class NotLikeTest extends TestCase
{
    public function testConstructEmptyArgs(): void
    {
        $notLike = new NotLike();
        self::assertEquals('', $notLike->getIdentifier());
        self::assertEquals('', $notLike->getLike());
    }

    public function testConstructWithArgs(): void
    {
        $notLike = new NotLike('bar', 'Foo%');

        $identifier = new Argument('bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $notLike->getIdentifier());

        $expression = new Argument('Foo%', ArgumentType::Value);
        self::assertEquals($expression, $notLike->getLike());
    }

    public function testAccessorsMutators(): void
    {
        $notLike = new NotLike();

        $notLike->setIdentifier('bar');
        $identifier = new Argument('bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $notLike->getIdentifier());

        $notLike->setLike('foo%');
        $expression = new Argument('foo%', ArgumentType::Value);
        self::assertEquals($expression, $notLike->getLike());

        $notLike->setSpecification('target = target');
        self::assertEquals('target = target', $notLike->getSpecification());
    }

    public function testGetExpressionData(): void
    {
        $notLike    = new NotLike('bar', 'Foo%');
        $identifier = new Argument('bar', ArgumentType::Identifier);
        $expression = new Argument('Foo%', ArgumentType::Value);

        $expressionData = $notLike->getExpressionData();

        self::assertEquals('%1$s NOT LIKE %2$s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }

    public function testInstanceOfPerSetters(): void
    {
        $notLike = new NotLike();
        self::assertInstanceOf(Like::class, $notLike->setIdentifier('bar'));
        self::assertInstanceOf(Like::class, $notLike->setSpecification('%1$s NOT LIKE %2$s'));
        self::assertInstanceOf(Like::class, $notLike->setLike('foo%'));
    }
}
