<?php

namespace LaminasTest\Db\Sql\Ddl\Constraint;

use Laminas\Db\Sql\Ddl\Constraint\AbstractConstraint;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractConstraint::class, 'setColumns')]
#[CoversMethod(AbstractConstraint::class, 'addColumn')]
#[CoversMethod(AbstractConstraint::class, 'getColumns')]
class AbstractConstraintTest extends TestCase
{
    /** @var AbstractConstraint */
    protected AbstractConstraint|MockObject $ac;

    /**
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->ac = $this->getMockBuilder(AbstractConstraint::class)->onlyMethods([])->getMock();
    }

    public function testSetColumns(): void
    {
        self::assertSame($this->ac, $this->ac->setColumns(['foo', 'bar']));
        self::assertEquals(['foo', 'bar'], $this->ac->getColumns());
    }

    public function testAddColumn(): void
    {
        self::assertSame($this->ac, $this->ac->addColumn('foo'));
        self::assertEquals(['foo'], $this->ac->getColumns());
    }

    public function testGetColumns(): void
    {
        $this->ac->setColumns(['foo', 'bar']);
        self::assertEquals(['foo', 'bar'], $this->ac->getColumns());
    }
}
