<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8;

use Laminas\Db\Adapter\Driver\Oci8\Oci8;
use Laminas\Db\Adapter\Driver\Oci8\Result;
use Laminas\Db\Adapter\Driver\Oci8\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function get_resource_type;
use function getenv;

#[CoversMethod(Statement::class, 'initialize')]
#[CoversMethod(Statement::class, 'getResource')]
#[CoversMethod(Statement::class, 'prepare')]
#[CoversMethod(Statement::class, 'isPrepared')]
#[CoversMethod(Statement::class, 'execute')]
#[Group('integration')]
#[Group('integration-oracle')]
final class StatementIntegrationTest extends TestCase
{
    /** @var array<string, string> */
    protected string|array|false $variables = [
        'hostname' => 'TESTS_LAMINAS_DB_ADAPTER_DRIVER_OCI8_HOSTNAME',
        'username' => 'TESTS_LAMINAS_DB_ADAPTER_DRIVER_OCI8_USERNAME',
        'password' => 'TESTS_LAMINAS_DB_ADAPTER_DRIVER_OCI8_PASSWORD',
    ];

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        foreach ($this->variables as $name => $value) {
            if (! getenv($value)) {
                $this->markTestSkipped(
                    'Missing required variable ' . $value . ' from phpunit.xml for this integration test'
                );
            }
            $this->variables[$name] = getenv($value);
        }

        if (! extension_loaded('oci8')) {
            $this->fail('The phpunit group integration-oracle was enabled, but the extension is not loaded.');
        }
    }

    public function testInitialize(): void
    {
        $ociResource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $statement = new Statement();
        self::assertSame($statement, $statement->initialize($ociResource));
        unset($stmtResource, $ociResource);
    }

    public function testGetResource(): void
    {
        $ociResource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $statement = new Statement();
        $statement->initialize($ociResource);
        $statement->prepare('SELECT * FROM DUAL');
        $resource = $statement->getResource();
        self::assertEquals('oci8 statement', get_resource_type($resource));
        unset($resource, $ociResource);
    }

    public function testPrepare(): void
    {
        $ociResource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $statement = new Statement();
        $statement->initialize($ociResource);
        self::assertFalse($statement->isPrepared());
        self::assertSame($statement, $statement->prepare('SELECT * FROM DUAL'));
        self::assertTrue($statement->isPrepared());
        unset($resource, $ociResource);
    }

    public function testExecute(): void
    {
        $oci8      = new Oci8($this->variables);
        $statement = $oci8->createStatement('SELECT * FROM DUAL');
        self::assertSame($statement, $statement->prepare());

        $result = $statement->execute();
        self::assertInstanceOf(Result::class, $result);

        unset($resource, $oci8);
    }
}
