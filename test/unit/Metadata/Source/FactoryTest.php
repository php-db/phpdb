<?php

namespace PhpDbTest\Metadata\Source;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Source\Factory;
use PhpDb\Metadata\Source\MysqlMetadata;
use PhpDb\Metadata\Source\OracleMetadata;
use PhpDb\Metadata\Source\PostgresqlMetadata;
use PhpDb\Metadata\Source\SqliteMetadata;
use PhpDb\Metadata\Source\SqlServerMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * @param class-string $expectedReturnClass
     */
    #[DataProvider('validAdapterProvider')]
    public function testCreateSourceFromAdapter(string $adapterName, string $expectedReturnClass): void
    {
        /**
         * @param string $platformName
         * @return Adapter&MockObject
         */
        $createAdapterForPlatform = function (string $platformName): Adapter&MockObject {
            $platform = $this->getMockBuilder(PlatformInterface::class)->getMock();
            $platform
                ->expects($this->any())
                ->method('getName')
                ->willReturn($platformName);

            $adapter = $this->getMockBuilder(Adapter::class)
                ->disableOriginalConstructor()
                ->getMock();

            $adapter
                ->expects($this->any())
                ->method('getPlatform')
                ->willReturn($platform);

            return $adapter;
        };

        $adapter = $createAdapterForPlatform($adapterName);
        $source  = Factory::createSourceFromAdapter($adapter);

        self::assertInstanceOf(MetadataInterface::class, $source);
        self::assertInstanceOf($expectedReturnClass, $source);
    }

    public static function validAdapterProvider(): array
    {
        return [
            // Description => [adapterName, expected return class]
            'MySQL'      => ['MySQL', MysqlMetadata::class],
            'SQLServer'  => ['SQLServer', SqlServerMetadata::class],
            'SQLite'     => ['SQLite', SqliteMetadata::class],
            'PostgreSQL' => ['PostgreSQL', PostgresqlMetadata::class],
            'Oracle'     => ['Oracle', OracleMetadata::class],
        ];
    }
}
