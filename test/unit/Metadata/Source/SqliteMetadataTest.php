<?php

namespace LaminasTest\Db\Metadata\Source;

use Exception;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Object\ConstraintKeyObject;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Object\TriggerObject;
use Laminas\Db\Metadata\Source\SqliteMetadata;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

#[RequiresPhpExtension('pdo_sqlite')]
final class SqliteMetadataTest extends TestCase
{
    protected SqliteMetadata $metadata;

    protected Adapter $adapter;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('I cannot test without the pdo_sqlite extension');
        }
        $this->adapter  = new Adapter([
            'driver' => 'Pdo',
            'dsn'    => 'sqlite::memory:',
        ]);
        $this->metadata = new SqliteMetadata($this->adapter);
    }

    public function testGetSchemas(): void
    {
        $schemas = $this->metadata->getSchemas();
        self::assertContains('main', $schemas);
        self::assertCount(1, $schemas);
    }

    public function testGetTableNames(): void
    {
        $tables = $this->metadata->getTableNames('main');
        self::assertCount(0, $tables);
    }

    /**
     * @throws Exception
     */
    public function testGetColumnNames(): void
    {
        $columns = $this->metadata->getColumnNames(null, 'main');
        self::assertCount(0, $columns);
    }

    public function testGetConstraints(): void
    {
        $constraints = $this->metadata->getConstraints(null, 'main');
        self::assertCount(0, $constraints);
        self::assertContainsOnlyInstancesOf(
            ConstraintObject::class,
            $constraints
        );
    }

    #[Group('Laminas-3719')]
    public function testGetConstraintKeys(): void
    {
        $keys = $this->metadata->getConstraintKeys(
            null,
            null,
            'main'
        );
        self::assertCount(0, $keys);
        self::assertContainsOnlyInstancesOf(
            ConstraintKeyObject::class,
            $keys
        );
    }

    public function testGetTriggers(): void
    {
        $triggers = $this->metadata->getTriggers('main');
        self::assertCount(0, $triggers);
        self::assertContainsOnlyInstancesOf(
            TriggerObject::class,
            $triggers
        );
    }
}
