<?php

namespace LaminasTest\Db\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Like;
use PhpDb\Sql\Predicate\NotLike;
use PHPUnit\Framework\TestCase;

class NotLikeTest extends TestCase
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
