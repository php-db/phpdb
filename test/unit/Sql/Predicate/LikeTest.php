<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Predicate\Like;
use PHPUnit\Framework\TestCase;

final class LikeTest extends TestCase
{
    public function testConstructEmptyArgs(): void
    {
        $like = new Like();
        self::assertEquals('', $like->getIdentifier());
        self::assertEquals('', $like->getLike());
    }

    public function testConstructWithArgs(): void
    {
        $like = new Like('bar', 'foo%');
        $identifier = new Argument('bar', ArgumentType::Identifier);
        $expression = new Argument('foo%', ArgumentType::Value);
        self::assertEquals($identifier, $like->getIdentifier());
        self::assertEquals($expression, $like->getLike());
    }

    public function testAccessorsMutators(): void
    {
        $like = new Like();

        $like->setIdentifier('bar');
        $expression = new Argument('bar', ArgumentType::Identifier);
        self::assertEquals($expression, $like->getIdentifier());

        $like->setLike('foo%');
        $expression = new Argument('foo%', ArgumentType::Value);
        self::assertEquals($expression, $like->getLike());

        $like->setSpecification('target = target');
        self::assertEquals('target = target', $like->getSpecification());
    }

    public function testGetExpressionData(): void
    {
        $like = new Like('bar', 'Foo%');
        $identifier = new Argument('bar', ArgumentType::Identifier);
        $expression = new Argument('Foo%', ArgumentType::Value);

        $expressionData = $like->getExpressionData();

        self::assertEquals('%1$s LIKE %2$s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());

        $like = new Like(['Foo%' => ArgumentType::Value], ['bar' => ArgumentType::Identifier]);
        $identifier = new Argument('Foo%', ArgumentType::Value);
        $expression = new Argument('bar', ArgumentType::Identifier);

        $expressionData = $like->getExpressionData();
        self::assertEquals('%1$s LIKE %2$s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());
    }

    public function testInstanceOfPerSetters(): void
    {
        $like = new Like();
        self::assertInstanceOf(Like::class, $like->setIdentifier('bar'));
        self::assertInstanceOf(Like::class, $like->setSpecification('%1$s LIKE %2$s'));
        self::assertInstanceOf(Like::class, $like->setLike('foo%'));
    }
}
