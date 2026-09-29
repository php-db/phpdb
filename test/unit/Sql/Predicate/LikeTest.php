<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\Like;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function accessorsMutators(): void
    {
        $like = new Like();

        // Test setIdentifier - first mutation
        $result = $like->setIdentifier('bar');

        // Verify fluent interface
        static::assertInstanceOf(Like::class, $result);

        // Verify first identifier mutation
        $identifier1 = $like->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $like->setIdentifier('baz');
        $identifier2 = $like->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());

        // Test setLike - first mutation
        $result = $like->setLike('foo%');

        // Verify fluent interface
        static::assertInstanceOf(Like::class, $result);

        // Verify first like mutation
        $likeValue1 = $like->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue1);
        static::assertSame('foo%', $likeValue1->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue1->getType());

        // Second mutation to verify mutability
        $like->setLike('bar%');
        $likeValue2 = $like->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue2);
        static::assertSame('bar%', $likeValue2->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue2->getType());

        // Test setSpecification (this returns string, not Argument)
        $result = $like->setSpecification('target = target');
        static::assertInstanceOf(Like::class, $result);
        static::assertSame('target = target', $like->getSpecification());

        // Second mutation to verify mutability
        $like->setSpecification('custom spec');
        static::assertSame('custom spec', $like->getSpecification());
    }

    #[Test]
    public function constructEmptyArgs(): void
    {
        $like = new Like();
        static::assertNull($like->getIdentifier());
        static::assertNull($like->getLike());
    }

    #[Test]
    public function constructWithArgs(): void
    {
        $like = new Like('bar', 'Foo%');

        $identifier = $like->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $likeValue = $like->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue);
        static::assertSame('Foo%', $likeValue->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue->getType());
    }

    #[Test]
    public function getExpressionData(): void
    {
        $like = new Like('bar', 'Foo%');

        $expressionData = $like->getExpressionData();

        // Verify specification
        static::assertSame('%s LIKE %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify like expression argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame('Foo%', $values[1]->getValue());
        static::assertEquals(ArgumentType::Value, $values[1]->getType());

        $like = new Like(Argument::value('Foo%'), Argument::identifier('bar'));

        $expressionData = $like->getExpressionData();

        // Verify specification
        static::assertSame('%s LIKE %s', $expressionData['spec']);

        // Verify expression values with custom types
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify identifier argument (now with Value type)
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('Foo%', $values[0]->getValue());
        static::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify like expression argument (now with Identifier type)
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame('bar', $values[1]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[1]->getType());
    }

    /**
     * A custom specification replaces the generated one rather than sitting unused
     * behind it.
     */
    #[Test]
    public function getExpressionDataPrefersACustomSpecification(): void
    {
        $like = new Like('foo.bar', 'baz%');
        $like->setSpecification('%1$s SOUNDS LIKE %2$s');

        static::assertSame('%1$s SOUNDS LIKE %2$s', $like->getExpressionData()['spec']);
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $like = new Like();
        $like->setLike('foo%');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_IDENTIFIER);
        $like->getExpressionData();
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenLikeNotSet(): void
    {
        $like = new Like();
        $like->setIdentifier('bar');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_LIKE_EXPRESSION);
        $like->getExpressionData();
    }

    #[Test]
    public function instanceOfPerSetters(): void
    {
        $like = new Like();
        static::assertInstanceOf(Like::class, $like->setIdentifier('bar'));
        static::assertInstanceOf(Like::class, $like->setSpecification('%s LIKE %s'));
        static::assertInstanceOf(Like::class, $like->setLike('foo%'));
    }
}
