<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\RowGatewayFeature;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forMissingAdapter')]
#[CoversMethod(RuntimeException::class, 'forMissingCurrentSequenceValue')]
#[CoversMethod(RuntimeException::class, 'forMissingPrimarySql')]
#[CoversMethod(RuntimeException::class, 'forMissingNextSequenceValue')]
#[CoversMethod(RuntimeException::class, 'forMissingPrimaryKey')]
#[CoversMethod(RuntimeException::class, 'forMissingPrimaryKeyInMetadata')]
#[CoversMethod(RuntimeException::class, 'forMissingSequenceResult')]
#[CoversMethod(RuntimeException::class, 'forMissingSqlInstance')]
#[CoversMethod(RuntimeException::class, 'forMissingStaticAdapter')]
#[CoversMethod(RuntimeException::class, 'forMissingTable')]
#[CoversMethod(RuntimeException::class, 'forNonArrayInsertData')]
#[CoversMethod(RuntimeException::class, 'forNonArrayMetadata')]
#[CoversMethod(RuntimeException::class, 'forTableMismatch')]
#[CoversMethod(RuntimeException::class, 'forUnexpectedResultSet')]
#[CoversMethod(RuntimeException::class, 'forUnnamedTableInMetadata')]
#[CoversMethod(RuntimeException::class, 'forUnnamedTableInRowGateway')]
#[CoversMethod(RuntimeException::class, 'forUnsupportedLastSequencePlatform')]
#[CoversMethod(RuntimeException::class, 'forUnsupportedNextSequencePlatform')]
#[CoversMethod(RuntimeException::class, 'forUnusablePrimaryKey')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'missing adapter'                    => [
                'forMissingAdapter',
                [],
                RuntimeException::MISSING_ADAPTER,
            ],
            'missing current sequence value'     => [
                'forMissingCurrentSequenceValue',
                [],
                RuntimeException::MISSING_CURRENT_SEQUENCE_VALUE,
            ],
            'missing primary sql'                => [
                'forMissingPrimarySql',
                [],
                RuntimeException::MISSING_PRIMARY_SQL,
            ],
            'missing next sequence value'        => [
                'forMissingNextSequenceValue',
                [],
                RuntimeException::MISSING_NEXT_SEQUENCE_VALUE,
            ],
            'missing primary key'                => [
                'forMissingPrimaryKey',
                [],
                RuntimeException::MISSING_PRIMARY_KEY,
            ],
            'missing primary key in metadata'    => [
                'forMissingPrimaryKeyInMetadata',
                [],
                RuntimeException::MISSING_PRIMARY_KEY_IN_METADATA,
            ],
            'missing sequence result'            => [
                'forMissingSequenceResult',
                [],
                RuntimeException::MISSING_SEQUENCE_RESULT,
            ],
            'missing sql instance'               => [
                'forMissingSqlInstance',
                [],
                RuntimeException::MISSING_SQL_INSTANCE,
            ],
            'missing static adapter'             => [
                'forMissingStaticAdapter',
                [],
                RuntimeException::MISSING_STATIC_ADAPTER,
            ],
            'missing table'                      => [
                'forMissingTable',
                [],
                RuntimeException::MISSING_TABLE,
            ],
            'non array insert data'              => [
                'forNonArrayInsertData',
                [],
                RuntimeException::NON_ARRAY_INSERT_DATA,
            ],
            'non array metadata'                 => [
                'forNonArrayMetadata',
                [],
                RuntimeException::NON_ARRAY_METADATA,
            ],
            'table mismatch'                     => [
                'forTableMismatch',
                ['Select'],
                RuntimeException::TABLE_MISMATCH,
            ],
            'unexpected result set'              => [
                'forUnexpectedResultSet',
                [RowGatewayFeature::class, RowPrototypeResultSet::class],
                RuntimeException::UNEXPECTED_RESULT_SET,
            ],
            'unnamed table in metadata'          => [
                'forUnnamedTableInMetadata',
                [],
                RuntimeException::UNNAMED_TABLE_IN_METADATA,
            ],
            'unnamed table in row gateway'       => [
                'forUnnamedTableInRowGateway',
                [],
                RuntimeException::UNNAMED_TABLE_IN_ROW_GATEWAY,
            ],
            'unsupported last sequence platform' => [
                'forUnsupportedLastSequencePlatform',
                [],
                RuntimeException::UNSUPPORTED_LAST_SEQUENCE_PLATFORM,
            ],
            'unsupported next sequence platform' => [
                'forUnsupportedNextSequencePlatform',
                [],
                RuntimeException::UNSUPPORTED_NEXT_SEQUENCE_PLATFORM,
            ],
            'unusable primary key'               => [
                'forUnusablePrimaryKey',
                [],
                RuntimeException::UNUSABLE_PRIMARY_KEY,
            ],
        ];
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = RuntimeException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::{$method}(...$arguments));
    }
}
