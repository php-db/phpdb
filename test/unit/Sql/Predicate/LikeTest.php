<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\Like;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Like::class, '__construct')]
#[CoversMethod(Like::class, 'setIdentifier')]
#[CoversMethod(Like::class, 'getIdentifier')]
#[CoversMethod(Like::class, 'setLike')]
#[CoversMethod(Like::class, 'getLike')]
#[CoversMethod(Like::class, 'setSpecification')]
#[CoversMethod(Like::class, 'getSpecification')]
#[CoversMethod(Like::class, 'getExpressionData')]
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
        $like = new Like('bar', 'Foo%');
        self::assertEquals(new Argument('bar', ArgumentType::Identifier), $like->getIdentifier());
        self::assertEquals(new Argument('Foo%', ArgumentType::Value), $like->getLike());
    }

    public function testAccessorsMutators(): void
    {
        $like = new Like();
        $like->setIdentifier('bar');
        self::assertEquals(new Argument('bar', ArgumentType::Identifier), $like->getIdentifier());
        $like->setLike('foo%');
        self::assertEquals(new Argument('foo%', ArgumentType::Value), $like->getLike());
        $like->setSpecification('target = target');
        self::assertEquals('target = target', $like->getSpecification());
    }

    public function testGetExpressionData(): void
    {
        $like       = new Like('bar', 'Foo%');
        $identifier = new Argument('bar', ArgumentType::Identifier);
        $expression = new Argument('Foo%', ArgumentType::Value);

        $expressionData = $like->getExpressionData();

        self::assertEquals('%1$s LIKE %2$s', $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $expression], $expressionData->getExpressionValues());

        $like       = new Like(['Foo%' => ArgumentType::Value], ['bar' => ArgumentType::Identifier]);
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

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $like = new Like();
        $like->setLike('foo%');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $like->getExpressionData();
    }

    public function testGetExpressionDataThrowsExceptionWhenLikeNotSet(): void
    {
        $like = new Like();
        $like->setIdentifier('bar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Like expression must be specified');
        $like->getExpressionData();
    }
}
