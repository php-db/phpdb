<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException
{
    final public const string MISSING_ADAPTER = 'This table does not have an Adapter setup';

    final public const string MISSING_CURRENT_SEQUENCE_VALUE = 'The sequence did not return a current value.';

    final public const string MISSING_NEXT_SEQUENCE_VALUE = 'The sequence did not return a next value.';

    final public const string MISSING_PRIMARY_KEY =
        'No information was provided to the RowGatewayFeature'
            . ' and/or no MetadataFeature could be consulted to find the primary key'
            . ' necessary for RowGateway object creation.';

    final public const string MISSING_PRIMARY_KEY_IN_METADATA =
        'A primary key for this column'
            . ' could not be found in the metadata.';

    final public const string MISSING_PRIMARY_SQL =
        'The primary Sql instance is not available;'
            . ' postInitialize() has not been run.';

    final public const string MISSING_SEQUENCE_RESULT = 'The sequence statement did not produce a result.';

    final public const string MISSING_SQL_INSTANCE =
        'The table gateway must be initialized with a Sql instance'
            . ' before this feature is applied.';

    final public const string MISSING_STATIC_ADAPTER = 'No database adapter was found in the static registry.';

    final public const string MISSING_TABLE = 'This table object does not have a valid table set.';

    final public const string NON_ARRAY_INSERT_DATA = 'The insert does not expose columns and values as arrays.';

    final public const string NON_ARRAY_METADATA = 'The MetadataFeature did not expose its metadata as an array.';

    final public const string TABLE_MISMATCH =
        'The table name of the provided %s object'
            . ' must match that of the table';

    final public const string UNEXPECTED_RESULT_SET =
        'This feature %s expects the ResultSet'
            . ' to be an instance of %s';

    final public const string UNNAMED_TABLE_IN_METADATA =
        'The table gateway must reference a named table'
            . ' before metadata can be resolved.';

    final public const string UNNAMED_TABLE_IN_ROW_GATEWAY =
        'The table gateway must reference a named table'
            . ' before a RowGateway prototype can be created.';

    final public const string UNSUPPORTED_LAST_SEQUENCE_PLATFORM =
        'Unsupported platform'
            . ' for retrieving last sequence id';

    final public const string UNSUPPORTED_NEXT_SEQUENCE_PLATFORM =
        'Unsupported platform'
            . ' for retrieving next sequence id';

    final public const string UNUSABLE_PRIMARY_KEY =
        'The MetadataFeature did not expose a usable primary key'
            . ' for RowGateway object creation.';

    public static function forMissingAdapter(): self
    {
        return new self(self::MISSING_ADAPTER);
    }

    public static function forMissingCurrentSequenceValue(): self
    {
        return new self(self::MISSING_CURRENT_SEQUENCE_VALUE);
    }

    public static function forMissingNextSequenceValue(): self
    {
        return new self(self::MISSING_NEXT_SEQUENCE_VALUE);
    }

    public static function forMissingPrimaryKey(): self
    {
        return new self(self::MISSING_PRIMARY_KEY);
    }

    public static function forMissingPrimaryKeyInMetadata(): self
    {
        return new self(self::MISSING_PRIMARY_KEY_IN_METADATA);
    }

    public static function forMissingPrimarySql(): self
    {
        return new self(self::MISSING_PRIMARY_SQL);
    }

    public static function forMissingSequenceResult(): self
    {
        return new self(self::MISSING_SEQUENCE_RESULT);
    }

    public static function forMissingSqlInstance(): self
    {
        return new self(self::MISSING_SQL_INSTANCE);
    }

    public static function forMissingStaticAdapter(): self
    {
        return new self(self::MISSING_STATIC_ADAPTER);
    }

    public static function forMissingTable(): self
    {
        return new self(self::MISSING_TABLE);
    }

    public static function forNonArrayInsertData(): self
    {
        return new self(self::NON_ARRAY_INSERT_DATA);
    }

    public static function forNonArrayMetadata(): self
    {
        return new self(self::NON_ARRAY_METADATA);
    }

    public static function forTableMismatch(string $statement): self
    {
        return new self(sprintf(self::TABLE_MISMATCH, $statement));
    }

    public static function forUnexpectedResultSet(string $feature, string $expected): self
    {
        return new self(sprintf(self::UNEXPECTED_RESULT_SET, $feature, $expected));
    }

    public static function forUnnamedTableInMetadata(): self
    {
        return new self(self::UNNAMED_TABLE_IN_METADATA);
    }

    public static function forUnnamedTableInRowGateway(): self
    {
        return new self(self::UNNAMED_TABLE_IN_ROW_GATEWAY);
    }

    public static function forUnsupportedLastSequencePlatform(): self
    {
        return new self(self::UNSUPPORTED_LAST_SEQUENCE_PLATFORM);
    }

    public static function forUnsupportedNextSequencePlatform(): self
    {
        return new self(self::UNSUPPORTED_NEXT_SEQUENCE_PLATFORM);
    }

    public static function forUnusablePrimaryKey(): self
    {
        return new self(self::UNUSABLE_PRIMARY_KEY);
    }
}
