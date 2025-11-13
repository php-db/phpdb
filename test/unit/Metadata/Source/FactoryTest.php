<?php

namespace PhpDbTest\Metadata\Source;

use Error;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\InvalidArgumentException;
use PhpDb\Metadata\Source\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Tests for the deprecated Factory class.
 *
 * Note: Factory::createSourceFromAdapter() is deprecated and references
 * non-existent metadata classes. These tests verify the expected behavior
 * (throwing exceptions) since the platform-specific metadata classes
 * (MysqlMetadata, SqlServerMetadata, etc.) no longer exist.
 */
class FactoryTest extends TestCase
{
    /**
     * Test that the deprecated factory throws errors for all platform types
     * since the referenced metadata classes don't exist.
     */
    #[DataProvider('platformProvider')]
    public function testCreateSourceFromAdapterThrowsErrorForNonExistentClasses(
        string $platformName,
        string $expectedClassName
    ): void
    {
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

        // Expect Error because the class doesn't exist
        $this->expectException(Error::class);
        $this->expectExceptionMessage("Class \"{$expectedClassName}\" not found");

        Factory::createSourceFromAdapter($adapter);
    }

    public static function platformProvider(): array
    {
        return [
            // Description => [platformName, expected class that doesn't exist]
            'MySQL'      => ['MySQL', 'PhpDb\Metadata\Source\MysqlMetadata'],
            'SQLServer'  => ['SQLServer', 'PhpDb\Metadata\Source\SqlServerMetadata'],
            'SQLite'     => ['SQLite', 'PhpDb\Metadata\Source\SqliteMetadata'],
            'PostgreSQL' => ['PostgreSQL', 'PhpDb\Metadata\Source\PostgresqlMetadata'],
            'Oracle'     => ['Oracle', 'PhpDb\Metadata\Source\OracleMetadata'],
        ];
    }

    public function testCreateSourceFromAdapterThrowsExceptionForUnrecognizedPlatform(): void
    {
        $platform = $this->getMockBuilder(PlatformInterface::class)->getMock();
        $platform
            ->expects($this->any())
            ->method('getName')
            ->willReturn('UnknownPlatform');

        $adapter = $this->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapter
            ->expects($this->any())
            ->method('getPlatform')
            ->willReturn($platform);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown adapter platform 'UnknownPlatform'");

        Factory::createSourceFromAdapter($adapter);
    }

    public function testFactoryMethodIsDeprecated(): void
    {
        // This test verifies that the factory method has the @deprecated annotation
        $reflection = new ReflectionMethod(Factory::class, 'createSourceFromAdapter');
        $docComment = $reflection->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@deprecated', $docComment);
        self::assertStringContainsString('to be removed in 3.0.0', $docComment);
    }
}