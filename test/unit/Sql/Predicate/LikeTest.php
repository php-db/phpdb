<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
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

        $identifier = $like->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $likeValue = $like->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue);
        self::assertEquals('Foo%', $likeValue->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue->getType());
    }

    public function testAccessorsMutators(): void
    {
        $like = new Like();

        // Test setIdentifier - first mutation
        $result = $like->setIdentifier('bar');

        // Verify fluent interface
        self::assertInstanceOf(Like::class, $result);

        // Verify first identifier mutation
        $identifier1 = $like->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $like->setIdentifier('baz');
        $identifier2 = $like->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());

        // Test setLike - first mutation
        $result = $like->setLike('foo%');

        // Verify fluent interface
        self::assertInstanceOf(Like::class, $result);

        // Verify first like mutation
        $likeValue1 = $like->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue1);
        self::assertEquals('foo%', $likeValue1->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue1->getType());

        // Second mutation to verify mutability
        $like->setLike('bar%');
        $likeValue2 = $like->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue2);
        self::assertEquals('bar%', $likeValue2->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue2->getType());

        // Test setSpecification (this returns string, not Argument)
        $result = $like->setSpecification('target = target');
        self::assertInstanceOf(Like::class, $result);
        self::assertEquals('target = target', $like->getSpecification());

        // Second mutation to verify mutability
        $like->setSpecification('custom spec');
        self::assertEquals('custom spec', $like->getSpecification());
    }

    public function testGetExpressionData(): void
    {
        $like = new Like('bar', 'Foo%');

        $expressionData = $like->getExpressionData();

        // Verify specification (uses new marker format)
        self::assertEquals('{"bar"} LIKE {?}', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify like expression argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals('Foo%', $values[1]->getValue());
        self::assertEquals(ArgumentType::Value, $values[1]->getType());

        $like = new Like(Argument::value('Foo%'), Argument::identifier('bar'));

        $expressionData = $like->getExpressionData();

        // Verify specification (uses new marker format)
        self::assertEquals('{?} LIKE {"bar"}', $expressionData['spec']);

        // Verify expression values with custom types
        $values = $expressionData['values'];
        self::assertCount(2, $values);

        // Verify identifier argument (now with Value type)
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('Foo%', $values[0]->getValue());
        self::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify like expression argument (now with Identifier type)
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals('bar', $values[1]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[1]->getType());
    }

    public function testInstanceOfPerSetters(): void
    {
        $like = new Like();
        self::assertInstanceOf(Like::class, $like->setIdentifier('bar'));
        self::assertInstanceOf(Like::class, $like->setSpecification('%s LIKE %s'));
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
