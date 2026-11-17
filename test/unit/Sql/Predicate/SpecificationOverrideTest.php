<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Predicate\Between;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PhpDb\Sql\Predicate\Like;
use PhpDb\Sql\Predicate\NotBetween;
use PhpDb\Sql\Predicate\NotIn;
use PhpDb\Sql\Predicate\NotLike;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Predicates built on AbstractExpression render a specification set through
 * setSpecification() in place of their default format, including their negated
 * variants. In and NotIn expand their value set's placeholder to one per list item.
 */
#[CoversMethod(AbstractExpression::class, 'setSpecification')]
#[CoversMethod(Between::class, 'getExpressionData')]
#[CoversMethod(In::class, 'getExpressionData')]
#[CoversMethod(IsNull::class, 'getExpressionData')]
#[CoversMethod(Like::class, 'getExpressionData')]
#[CoversMethod(Operator::class, 'getExpressionData')]
#[Group('unit')]
final class SpecificationOverrideTest extends TestCase
{
    /** @return array<string, array{AbstractExpression, string, string}> */
    public static function predicateProvider(): array
    {
        return [
            'operator'    => [
                new Operator('a', Operator::OPERATOR_EQUAL_TO, 1),
                '%1$s <=> %2$s',
                'SELECT "t".* FROM "t" WHERE "a" <=> \'1\'',
            ],
            'like'        => [
                new Like('a', 'b%'),
                '%1$s SOUNDS LIKE %2$s',
                'SELECT "t".* FROM "t" WHERE "a" SOUNDS LIKE \'b%\'',
            ],
            'not like'    => [
                new NotLike('a', 'b%'),
                'NOT (%1$s SOUNDS LIKE %2$s)',
                'SELECT "t".* FROM "t" WHERE NOT ("a" SOUNDS LIKE \'b%\')',
            ],
            'between'     => [
                new Between('a', 1, 2),
                '%1$s BETWEEN SYMMETRIC %2$s AND %3$s',
                'SELECT "t".* FROM "t" WHERE "a" BETWEEN SYMMETRIC \'1\' AND \'2\'',
            ],
            'not between' => [
                new NotBetween('a', 1, 2),
                '%1$s NOT BETWEEN SYMMETRIC %2$s AND %3$s',
                'SELECT "t".* FROM "t" WHERE "a" NOT BETWEEN SYMMETRIC \'1\' AND \'2\'',
            ],
            'is null'     => [new IsNull('a'), '%1$s IS UNKNOWN', 'SELECT "t".* FROM "t" WHERE "a" IS UNKNOWN'],
            'is not null' => [
                new IsNotNull('a'),
                '%1$s IS NOT UNKNOWN',
                'SELECT "t".* FROM "t" WHERE "a" IS NOT UNKNOWN',
            ],
            'in'          => [
                new In('a', [1, 2]),
                '%s = ANY (%s)',
                'SELECT "t".* FROM "t" WHERE "a" = ANY (\'1\', \'2\')',
            ],
            'not in'      => [
                new NotIn('a', [1, 2]),
                '%s <> ALL (%s)',
                'SELECT "t".* FROM "t" WHERE "a" <> ALL (\'1\', \'2\')',
            ],
        ];
    }

    #[Test]
    #[DataProvider('predicateProvider')]
    public function rendersTheCustomSpecification(
        AbstractExpression $predicate,
        string $specification,
        string $expectedSql,
    ): void {
        $predicate->setSpecification($specification);

        static::assertSame(
            $expectedSql,
            (new Select('t'))->where($predicate)
                ->getSqlString(new TrustingSql92Platform()),
        );
    }
}
