<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Predicate\Between;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNull;
use PhpDb\Sql\Predicate\Like;
use PhpDb\Sql\Predicate\Operator;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Predicates built on AbstractExpression render a specification set through
 * setSpecification() in place of their default format.
 */
#[CoversMethod(AbstractExpression::class, 'setSpecification')]
#[CoversMethod(Between::class, 'getExpressionData')]
#[CoversMethod(In::class, 'getExpressionData')]
#[CoversMethod(IsNull::class, 'getExpressionData')]
#[CoversMethod(Like::class, 'getExpressionData')]
#[CoversMethod(Operator::class, 'getExpressionData')]
#[Group('unit')]
final class CustomSpecificationTest extends TestCase
{
    /** @return array<string, array{AbstractExpression, string}> */
    public static function predicateProvider(): array
    {
        return [
            'operator' => [new Operator('a', Operator::OPERATOR_EQUAL_TO, 1), '%1$s <=> %2$s'],
            'like'     => [new Like('a', 'b%'), '%1$s SOUNDS LIKE %2$s'],
            'between'  => [new Between('a', 1, 2), '%1$s BETWEEN SYMMETRIC %2$s AND %3$s'],
            'is null'  => [new IsNull('a'), '%1$s IS UNKNOWN'],
            'in'       => [new In('a', [1, 2]), '%s = ANY (%s)'],
        ];
    }

    #[Test]
    #[DataProvider('predicateProvider')]
    public function rendersTheCustomSpecification(
        AbstractExpression $predicate,
        string $specification,
    ): void {
        $predicate->setSpecification($specification);

        static::assertSame($specification, $predicate->getExpressionData()['spec']);
    }
}
