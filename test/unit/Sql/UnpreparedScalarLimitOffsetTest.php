<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDbTest\TestAsset\ObjectToString;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PhpDbTest\TestAsset\UnpreparedScalarLimitSelect;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

use function sprintf;

#[Group('unit')]
#[CoversMethod(AbstractSql::class, 'buildSqlString')]
#[CoversMethod(AbstractSql::class, 'createSqlFromSpecificationAndParameters')]
#[CoversMethod(AbstractSql::class, 'flattenParameterList')]
#[CoversMethod(AbstractSql::class, 'formatParameterList')]
#[CoversMethod(AbstractSql::class, 'renderParameter')]
#[CoversMethod(AbstractSql::class, 'stringifyParameter')]
final class UnpreparedScalarLimitOffsetTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, list<array<int|string, string>|null>>}>
     */
    public static function limitSpecificationProvider(): array
    {
        return [
            'slot without parameter specification' => [['LIMIT %1$s' => [null]]],
            'slot with parameter specification'    => [['LIMIT %1$s' => [[1 => '%1$s']]]],
            'slot combined by separator'           => [['LIMIT %1$s' => [[1 => '%1$s', 'combinedby' => ', ']]]],
        ];
    }

    /**
     * @return array<string, array{
     *     0: int|float|string|Stringable|null,
     *     1: int|float|string|Stringable|null,
     *     2: string,
     * }>
     */
    public static function scalarLimitOffsetProvider(): array
    {
        return [
            'int limit'                   => [10, null, 'SELECT "t".* FROM "t" LIMIT 10'],
            'float limit'                 => [2.5, null, 'SELECT "t".* FROM "t" LIMIT 2.5'],
            'numeric string limit'        => ['10', null, 'SELECT "t".* FROM "t" LIMIT 10'],
            'stringable limit'            => [new ObjectToString('10'), null, 'SELECT "t".* FROM "t" LIMIT 10'],
            'int offset only'             => [null, 5, 'SELECT "t".* FROM "t" LIMIT 18446744073709551615 OFFSET 5'],
            'float offset only'           => [null, 5.0, 'SELECT "t".* FROM "t" LIMIT 18446744073709551615 OFFSET 5'],
            'int limit and int offset'    => [10, 20, 'SELECT "t".* FROM "t" LIMIT 10 OFFSET 20'],
            'int limit and string offset' => [10, '20', 'SELECT "t".* FROM "t" LIMIT 10 OFFSET 20'],
        ];
    }

    #[Test]
    public function rendersFloatsWithTheSamePrecisionAsVsprintf(): void
    {
        $select = new UnpreparedScalarLimitSelect('t', 0.1 + 0.2);

        self::assertSame(
            sprintf('SELECT "t".* FROM "t" LIMIT %s', 0.1 + 0.2),
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @param array<string, list<array<int|string, string>|null>> $specification
     */
    #[Test]
    #[DataProvider('limitSpecificationProvider')]
    public function rendersIntegerLimitThroughArraySpecifications(array $specification): void
    {
        $select = new UnpreparedScalarLimitSelect('t', 10, limitSpecification: $specification);

        self::assertSame('SELECT "t".* FROM "t" LIMIT 10', $select->getSqlString(new TrustingSql92Platform()));
    }

    #[Test]
    #[DataProvider('scalarLimitOffsetProvider')]
    public function rendersScalarLimitAndOffsetReturnedUnquotedByProcessHooks(
        int|float|string|Stringable|null $limit,
        int|float|string|Stringable|null $offset,
        string $expected,
    ): void {
        $select = new UnpreparedScalarLimitSelect('t', $limit, $offset);

        self::assertSame($expected, $select->getSqlString(new TrustingSql92Platform()));
    }

    #[Test]
    public function stillRejectsNestedListForSlotWithoutParameterSpecification(): void
    {
        $select = new UnpreparedScalarLimitSelect('t', ['1', '2'], limitSpecification: ['LIMIT %1$s' => [null]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::UNSUPPORTED_PARAMETER_COUNT_OF, 2));

        $select->getSqlString(new TrustingSql92Platform());
    }

    #[Test]
    public function stillRejectsNestedListReturnedForStringSpecification(): void
    {
        $select = new UnpreparedScalarLimitSelect('t', ['1', '2']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::UNSUPPORTED_PARAMETER_COUNT_OF, 2));

        $select->getSqlString(new TrustingSql92Platform());
    }
}
