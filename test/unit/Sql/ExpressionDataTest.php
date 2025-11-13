<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\ExpressionData;
use PhpDb\Sql\ExpressionPart;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(ExpressionData::class, '__construct')]
#[CoversMethod(ExpressionData::class, 'addExpressionPart')]
#[CoversMethod(ExpressionData::class, 'addExpressionParts')]
#[CoversMethod(ExpressionData::class, 'getExpressionPart')]
#[CoversMethod(ExpressionData::class, 'getExpressionParts')]
#[CoversMethod(ExpressionData::class, 'getExpressionSpecification')]
#[CoversMethod(ExpressionData::class, 'getExpressionValues')]
#[CoversMethod(ExpressionData::class, 'rewind')]
#[CoversMethod(ExpressionData::class, 'current')]
#[CoversMethod(ExpressionData::class, 'key')]
#[CoversMethod(ExpressionData::class, 'next')]
#[CoversMethod(ExpressionData::class, 'valid')]
#[CoversMethod(ExpressionData::class, 'count')]
final class ExpressionDataTest extends TestCase
{
    public function testConstructorWithoutArguments(): void
    {
        $expressionData = new ExpressionData();

        self::assertCount(0, $expressionData);
        self::assertEquals('', $expressionData->getExpressionSpecification());
        self::assertEquals([], $expressionData->getExpressionValues());
    }

    public function testConstructorWithStringSpecification(): void
    {
        $expressionData = new ExpressionData('%s = %s', [
            Argument::identifier('column'),
            Argument::value('value'),
        ]);

        self::assertCount(1, $expressionData);
        self::assertEquals('%s = %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('column'),
            Argument::value('value'),
        ], $expressionData->getExpressionValues());
    }

    public function testConstructorWithExpressionPart(): void
    {
        $part = new ExpressionPart('%s IS NULL', [Argument::identifier('field')]);
        $expressionData = new ExpressionData($part);

        self::assertCount(1, $expressionData);
        self::assertEquals('%s IS NULL', $expressionData->getExpressionSpecification());
    }

    public function testAddExpressionPartWithString(): void
    {
        $expressionData = new ExpressionData();
        $result = $expressionData->addExpressionPart('%s > %s', [
            Argument::identifier('age'),
            Argument::value(18),
        ]);

        self::assertSame($expressionData, $result); // Fluent interface
        self::assertCount(1, $expressionData);
    }

    public function testAddExpressionPartWithExpressionPart(): void
    {
        $expressionData = new ExpressionData();
        $part = new ExpressionPart('%s < %s', [
            Argument::identifier('price'),
            Argument::value(100),
        ]);

        $expressionData->addExpressionPart($part);

        self::assertCount(1, $expressionData);
        self::assertSame($part, $expressionData->getExpressionPart(0));
    }

    public function testAddMultipleExpressionParts(): void
    {
        $expressionData = new ExpressionData('%s = %s', [
            Argument::identifier('status'),
            Argument::value('active'),
        ]);

        $expressionData->addExpressionPart('AND %s > %s', [
            Argument::identifier('count'),
            Argument::value(0),
        ]);

        self::assertCount(2, $expressionData);
        self::assertEquals('%s = %s AND %s > %s', $expressionData->getExpressionSpecification());
    }

    public function testAddExpressionPartsWithoutBrackets(): void
    {
        $expressionData = new ExpressionData();
        $parts = [
            new ExpressionPart('%s = %s', [Argument::identifier('a'), Argument::value(1)]),
            new ExpressionPart('%s = %s', [Argument::identifier('b'), Argument::value(2)]),
        ];

        $result = $expressionData->addExpressionParts($parts);

        self::assertSame($expressionData, $result); // Fluent interface
        self::assertCount(2, $expressionData);
        self::assertEquals('%s = %s %s = %s', $expressionData->getExpressionSpecification());
    }

    public function testAddExpressionPartsWithBrackets(): void
    {
        $expressionData = new ExpressionData();
        $parts = [
            new ExpressionPart('%s = %s', [Argument::identifier('x'), Argument::value(1)]),
            new ExpressionPart('OR %s = %s', [Argument::identifier('y'), Argument::value(2)]),
        ];

        $expressionData->addExpressionParts($parts, true);

        self::assertCount(2, $expressionData);
        $spec = $expressionData->getExpressionSpecification();
        self::assertStringStartsWith('(', $spec);
        self::assertStringEndsWith(')', $spec);
    }

    public function testAddExpressionPartsThrowsExceptionForInvalidPart(): void
    {
        $expressionData = new ExpressionData();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression parts must be of type ExpressionPart');
        $expressionData->addExpressionParts(['not an ExpressionPart']);
    }

    public function testGetExpressionPart(): void
    {
        $part1 = new ExpressionPart('%s = %s');
        $part2 = new ExpressionPart('%s != %s');

        $expressionData = new ExpressionData();
        $expressionData->addExpressionPart($part1);
        $expressionData->addExpressionPart($part2);

        self::assertSame($part1, $expressionData->getExpressionPart(0));
        self::assertSame($part2, $expressionData->getExpressionPart(1));
    }

    public function testGetExpressionPartThrowsExceptionForInvalidPosition(): void
    {
        $expressionData = new ExpressionData();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression part does not exist');
        $expressionData->getExpressionPart(0);
    }

    public function testGetExpressionParts(): void
    {
        $part1 = new ExpressionPart('%s');
        $part2 = new ExpressionPart('%s');

        $expressionData = new ExpressionData();
        $expressionData->addExpressionPart($part1);
        $expressionData->addExpressionPart($part2);

        $parts = $expressionData->getExpressionParts();

        self::assertIsArray($parts);
        self::assertCount(2, $parts);
        self::assertSame($part1, $parts[0]);
        self::assertSame($part2, $parts[1]);
    }

    public function testIteratorInterface(): void
    {
        $part1 = new ExpressionPart('PART1');
        $part2 = new ExpressionPart('PART2');
        $part3 = new ExpressionPart('PART3');

        $expressionData = new ExpressionData();
        $expressionData->addExpressionPart($part1);
        $expressionData->addExpressionPart($part2);
        $expressionData->addExpressionPart($part3);

        $iterations = 0;
        $expectedParts = [$part1, $part2, $part3];

        foreach ($expressionData as $key => $part) {
            self::assertEquals($iterations, $key);
            self::assertSame($expectedParts[$iterations], $part);
            $iterations++;
        }

        self::assertEquals(3, $iterations);
    }

    public function testCountable(): void
    {
        $expressionData = new ExpressionData();
        self::assertCount(0, $expressionData);

        $expressionData->addExpressionPart('%s');
        self::assertCount(1, $expressionData);

        $expressionData->addExpressionPart('%s');
        self::assertCount(2, $expressionData);
    }

    public function testGetExpressionValues(): void
    {
        $expressionData = new ExpressionData();
        $expressionData->addExpressionPart('%s = %s', [
            Argument::identifier('col1'),
            Argument::value('val1'),
        ]);
        $expressionData->addExpressionPart('AND %s = %s', [
            Argument::identifier('col2'),
            Argument::value('val2'),
        ]);

        $values = $expressionData->getExpressionValues();

        self::assertCount(4, $values);
        self::assertEquals(Argument::identifier('col1'), $values[0]);
        self::assertEquals(Argument::value('val1'), $values[1]);
        self::assertEquals(Argument::identifier('col2'), $values[2]);
        self::assertEquals(Argument::value('val2'), $values[3]);
    }

    public function testIteratorRewind(): void
    {
        $expressionData = new ExpressionData();
        $expressionData->addExpressionPart('PART1');
        $expressionData->addExpressionPart('PART2');

        // Iterate once
        foreach ($expressionData as $part) {
            // Just iterate
        }

        // Iterate again to ensure rewind works
        $count = 0;
        foreach ($expressionData as $part) {
            $count++;
        }

        self::assertEquals(2, $count);
    }
}