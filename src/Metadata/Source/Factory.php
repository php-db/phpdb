<?php

namespace PhpDb\Metadata\Source;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\InvalidArgumentException;
use PhpDb\Metadata\MetadataInterface;

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
     * @deprecated to be removed in 3.0.0
     */
    public static function createSourceFromAdapter(AdapterInterface $adapter)
    {
        $platformName = $adapter->getPlatform()->getName();

        switch ($platformName) {
            case 'MySQL':
                return new MysqlMetadata($adapter);
            case 'SQLServer':
                return new SqlServerMetadata($adapter);
            case 'SQLite':
                return new SqliteMetadata($adapter);
            case 'PostgreSQL':
                return new PostgresqlMetadata($adapter);
            case 'Oracle':
                return new OracleMetadata($adapter);
            default:
                throw new InvalidArgumentException("Unknown adapter platform '{$platformName}'");
        }
    }
}
