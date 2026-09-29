<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Ddl\Constraint\AbstractConstraint;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractConstraint::class, 'setColumns')]
#[CoversMethod(AbstractConstraint::class, 'addColumn')]
#[CoversMethod(AbstractConstraint::class, 'getColumns')]
final class AbstractConstraintTest extends TestCase
{
    protected MockObject $ac;

    #[Test]
    public function addColumn(): void
    {
        static::assertSame($this->ac, $this->ac->addColumn('foo'));
        static::assertEquals(['foo'], $this->ac->getColumns());
    }

    #[Test]
    public function getColumns(): void
    {
        $this->ac->setColumns(['foo', 'bar']);
        static::assertEquals(['foo', 'bar'], $this->ac->getColumns());
    }

    #[Test]
    public function setColumns(): void
    {
        static::assertSame($this->ac, $this->ac->setColumns(['foo', 'bar']));
        static::assertEquals(['foo', 'bar'], $this->ac->getColumns());
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->ac = $this->getMockBuilder(AbstractConstraint::class)->onlyMethods([])->getMock();
    }
}
