<?php

namespace PhpDbTest\Adapter\Driver\Pdo;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Exception\RuntimeException;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPdo::class, 'getDatabasePlatformName')]
#[CoversMethod(AbstractPdo::class, 'getResultPrototype')]
final class PdoTest extends TestCase
{
    protected TestPdo $pdo;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->pdo = new TestPdo([]);
    }

    public function testGetDatabasePlatformName(): void
    {
        // Test platform name for SqlServer
        $this->pdo->getConnection()->setConnectionParameters(['pdodriver' => 'sqlsrv']);
        self::assertEquals('SqlServer', $this->pdo->getDatabasePlatformName());
        self::assertEquals('SQLServer', $this->pdo->getDatabasePlatformName(DriverInterface::NAME_FORMAT_NATURAL));
    }

    /** @psalm-return array<array-key, array{0: int|string, 1: null|string, 2: string}> */
    public static function getParamsAndType(): array
    {
        return [
            ['foo', null, ':foo'],
            ['foo_bar', null, ':foo_bar'],
            ['123foo', null, ':123foo'],
            [1, null, '?'],
            ['1', null, '?'],
            ['foo', DriverInterface::PARAMETERIZATION_NAMED, ':foo'],
            ['foo_bar', DriverInterface::PARAMETERIZATION_NAMED, ':foo_bar'],
            ['123foo', DriverInterface::PARAMETERIZATION_NAMED, ':123foo'],
            [1, DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            ['1', DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            [':foo', null, ':foo'],
        ];
    }

    #[DataProvider('getParamsAndType')]
    public function testFormatParameterName(int|string $name, ?string $type, string $expected): void
    {
        $result = $this->pdo->formatParameterName($name, $type);
        $this->assertEquals($expected, $result);
    }

    /** @psalm-return array<array-key, array{0: string}> */
    public static function getInvalidParamName(): array
    {
        return [
            ['foo%'],
            ['foo-'],
            ['foo$'],
            ['foo0!'],
        ];
    }

    #[DataProvider('getInvalidParamName')]
    public function testFormatParameterNameWithInvalidCharacters(string $name): void
    {
        $this->expectException(RuntimeException::class);
        $this->pdo->formatParameterName($name);
    }

    public function testGetResultPrototype(): void
    {
        $resultPrototype = $this->pdo->getResultPrototype();

        self::assertInstanceOf(Result::class, $resultPrototype);
    }
}
