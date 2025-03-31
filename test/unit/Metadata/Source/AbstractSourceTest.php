<?php

namespace LaminasTest\Db\Metadata\Source;

use Laminas\Db\Metadata\Object\ConstraintKeyObject;
use Laminas\Db\Metadata\Source\AbstractSource;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class AbstractSourceTest extends TestCase
{
    /** @var AbstractSource */
    protected MockObject|AbstractSource $abstractSourceMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->abstractSourceMock = $this->getMockForAbstractClass(
            AbstractSource::class,
            [],
            '',
            false
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testGetConstraintKeys(): void
    {
        $refProp = new ReflectionProperty($this->abstractSourceMock, 'data');
        /** @psalm-suppress UnusedMethodCall */
        $refProp->setAccessible(true);

        // internal data
        $data = [
            'constraint_references' => [
                'foo_schema' => [
                    [
                        'constraint_name'        => 'bam_constraint',
                        'update_rule'            => 'UP',
                        'delete_rule'            => 'DOWN',
                        'referenced_table_name'  => 'another_table',
                        'referenced_column_name' => 'another_column',
                    ],
                ],
            ],
            'constraint_keys'       => [
                'foo_schema' => [
                    [
                        'table_name'       => 'bar_table',
                        'constraint_name'  => 'bam_constraint',
                        'column_name'      => 'a',
                        'ordinal_position' => 1,
                    ],
                ],
            ],
        ];

        $refProp->setValue($this->abstractSourceMock, $data);
        $constraints = $this->abstractSourceMock->getConstraintKeys('bam_constraint', 'bar_table', 'foo_schema');
        self::assertCount(1, $constraints);

        $constraintKeyObj = $constraints[0];
        self::assertInstanceOf(ConstraintKeyObject::class, $constraintKeyObj);

        // check value object is mapped correctly
        self::assertEquals('a', $constraintKeyObj->getColumnName());
        self::assertEquals(1, $constraintKeyObj->getOrdinalPosition());
        self::assertEquals('another_table', $constraintKeyObj->getReferencedTableName());
        self::assertEquals('another_column', $constraintKeyObj->getReferencedColumnName());
        self::assertEquals('UP', $constraintKeyObj->getForeignKeyUpdateRule());
        self::assertEquals('DOWN', $constraintKeyObj->getForeignKeyDeleteRule());
    }
}
