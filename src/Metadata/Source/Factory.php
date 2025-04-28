<?php

namespace Laminas\Db\Metadata\Source;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Exception\InvalidArgumentException;
use Laminas\Db\Metadata\MetadataInterface;

/**
 * Source metadata factory.
 */
class Factory
{
    /**
     * Create source from adapter
     *
     * @return MetadataInterface
     * @throws InvalidArgumentException If adapter platform name not recognized.
     */
    public static function createSourceFromAdapter(Adapter $adapter)
    {
        $platformName = $adapter->getPlatform()->getName();

        return match ($platformName) {
            'MySQL' => new MysqlMetadata($adapter),
            'SQLServer' => new SqlServerMetadata($adapter),
            'SQLite' => new SqliteMetadata($adapter),
            'PostgreSQL' => new PostgresqlMetadata($adapter),
            'Oracle' => new OracleMetadata($adapter),
            default => throw new InvalidArgumentException("Unknown adapter platform '{$platformName}'"),
        };
    }
}
