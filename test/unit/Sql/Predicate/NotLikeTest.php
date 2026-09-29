<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Like;
use PhpDb\Sql\Predicate\NotLike;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotLikeTest extends TestCase
{
    #[Test]
    public function accessorsMutators(): void
    {
        $notLike = new NotLike();

        // Test setIdentifier - first mutation
        $result = $notLike->setIdentifier('bar');

        // Verify fluent interface
        static::assertInstanceOf(Like::class, $result);

        // Verify first identifier mutation
        $identifier1 = $notLike->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $notLike->setIdentifier('baz');
        $identifier2 = $notLike->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());

        // Test setLike - first mutation
        $result = $notLike->setLike('foo%');

        // Verify fluent interface
        static::assertInstanceOf(Like::class, $result);

        // Verify first like mutation
        $likeValue1 = $notLike->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue1);
        static::assertSame('foo%', $likeValue1->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue1->getType());

        // Second mutation to verify mutability
        $notLike->setLike('bar%');
        $likeValue2 = $notLike->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue2);
        static::assertSame('bar%', $likeValue2->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue2->getType());

        // Test setSpecification (this returns string, not Argument)
        $result = $notLike->setSpecification('target = target');
        static::assertInstanceOf(Like::class, $result);
        static::assertSame('target = target', $notLike->getSpecification());

        // Second mutation to verify mutability
        $notLike->setSpecification('custom spec');
        static::assertSame('custom spec', $notLike->getSpecification());
    }

    #[Test]
    public function constructEmptyArgs(): void
    {
        $notLike = new NotLike();
        static::assertNull($notLike->getIdentifier());
        static::assertNull($notLike->getLike());
    }

    #[Test]
    public function constructWithArgs(): void
    {
        $notLike = new NotLike('bar', 'Foo%');

        $identifier = $notLike->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $likeValue = $notLike->getLike();
        static::assertInstanceOf(ArgumentInterface::class, $likeValue);
        static::assertSame('Foo%', $likeValue->getValue());
        static::assertEquals(ArgumentType::Value, $likeValue->getType());
    }

    #[Test]
    public function getExpressionData(): void
    {
        $notLike = new NotLike('bar', 'Foo%');

        $expressionData = $notLike->getExpressionData();

        // Verify specification
        static::assertSame('%s NOT LIKE %s', $expressionData['spec']);

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
    }

    #[Test]
    public function instanceOfPerSetters(): void
    {
        $notLike = new NotLike();
        static::assertInstanceOf(Like::class, $notLike->setIdentifier('bar'));
        static::assertInstanceOf(Like::class, $notLike->setSpecification('%s NOT LIKE %s'));
        static::assertInstanceOf(Like::class, $notLike->setLike('foo%'));
    }
}
