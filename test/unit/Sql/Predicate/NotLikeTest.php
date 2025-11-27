<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument\ArgumentInterface;
use PhpDb\Sql\Argument\ArgumentType;
use PhpDb\Sql\Predicate\Like;
use PhpDb\Sql\Predicate\NotLike;
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

        $identifier = $notLike->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $likeValue = $notLike->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue);
        self::assertEquals('Foo%', $likeValue->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue->getType());
    }

    public function testAccessorsMutators(): void
    {
        $notLike = new NotLike();

        // Test setIdentifier - first mutation
        $result = $notLike->setIdentifier('bar');

        // Verify fluent interface
        self::assertInstanceOf(Like::class, $result);

        // Verify first identifier mutation
        $identifier1 = $notLike->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $notLike->setIdentifier('baz');
        $identifier2 = $notLike->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());

        // Test setLike - first mutation
        $result = $notLike->setLike('foo%');

        // Verify fluent interface
        self::assertInstanceOf(Like::class, $result);

        // Verify first like mutation
        $likeValue1 = $notLike->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue1);
        self::assertEquals('foo%', $likeValue1->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue1->getType());

        // Second mutation to verify mutability
        $notLike->setLike('bar%');
        $likeValue2 = $notLike->getLike();
        self::assertInstanceOf(ArgumentInterface::class, $likeValue2);
        self::assertEquals('bar%', $likeValue2->getValue());
        self::assertEquals(ArgumentType::Value, $likeValue2->getType());

        // Test setSpecification (this returns string, not Argument)
        $result = $notLike->setSpecification('target = target');
        self::assertInstanceOf(Like::class, $result);
        self::assertEquals('target = target', $notLike->getSpecification());

        // Second mutation to verify mutability
        $notLike->setSpecification('custom spec');
        self::assertEquals('custom spec', $notLike->getSpecification());
    }

    public function testGetExpressionData(): void
    {
        $notLike = new NotLike('bar', 'Foo%');

        $expressionData = $notLike->getExpressionData();

        // Verify specification
        self::assertEquals('%1$s NOT LIKE %2$s', $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(2, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify like expression argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals('Foo%', $values[1]->getValue());
        self::assertEquals(ArgumentType::Value, $values[1]->getType());
    }

    public function testInstanceOfPerSetters(): void
    {
        $notLike = new NotLike();
        self::assertInstanceOf(Like::class, $notLike->setIdentifier('bar'));
        self::assertInstanceOf(Like::class, $notLike->setSpecification('%1$s NOT LIKE %2$s'));
        self::assertInstanceOf(Like::class, $notLike->setLike('foo%'));
    }
}
