<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Ddl\Constraint\AbstractConstraint;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractConstraint::class, '__construct')]
#[CoversMethod(AbstractConstraint::class, 'setName')]
#[CoversMethod(AbstractConstraint::class, 'getName')]
#[CoversMethod(AbstractConstraint::class, 'setColumns')]
#[CoversMethod(AbstractConstraint::class, 'addColumn')]
#[CoversMethod(AbstractConstraint::class, 'getColumns')]
#[CoversMethod(AbstractConstraint::class, 'getExpressionData')]
#[CoversMethod(ForeignKey::class, '__construct')]
#[CoversMethod(ForeignKey::class, 'setName')]
#[CoversMethod(ForeignKey::class, 'getName')]
#[CoversMethod(ForeignKey::class, 'setReferenceTable')]
#[CoversMethod(ForeignKey::class, 'getReferenceTable')]
#[CoversMethod(ForeignKey::class, 'setReferenceColumn')]
#[CoversMethod(ForeignKey::class, 'getReferenceColumn')]
#[CoversMethod(ForeignKey::class, 'setOnDeleteRule')]
#[CoversMethod(ForeignKey::class, 'getOnDeleteRule')]
#[CoversMethod(ForeignKey::class, 'setOnUpdateRule')]
#[CoversMethod(ForeignKey::class, 'getOnUpdateRule')]
#[CoversMethod(ForeignKey::class, 'getExpressionData')]
final class ForeignKeyTest extends TestCase
{
    #[Test]
    public function getExpressionData(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam', 'CASCADE', 'SET NULL');

        $expressionData = $fk->getExpressionData();

        // Verify specification
        static::assertSame(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
            $expressionData['spec'],
        );

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(6, $values);

        // Verify constraint name
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify column name
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame('bar', $values[1]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[1]->getType());

        // Verify reference table
        static::assertInstanceOf(ArgumentInterface::class, $values[2]);
        static::assertSame('baz', $values[2]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[2]->getType());

        // Verify reference column
        static::assertInstanceOf(ArgumentInterface::class, $values[3]);
        static::assertSame('bam', $values[3]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[3]->getType());

        // Verify on delete rule
        static::assertInstanceOf(ArgumentInterface::class, $values[4]);
        static::assertSame('CASCADE', $values[4]->getValue());
        static::assertEquals(ArgumentType::Literal, $values[4]->getType());

        // Verify on update rule
        static::assertInstanceOf(ArgumentInterface::class, $values[5]);
        static::assertSame('SET NULL', $values[5]->getValue());
        static::assertEquals(ArgumentType::Literal, $values[5]->getType());
    }

    #[Test]
    public function setName(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setName('xxxx');

        // Verify fluent interface
        static::assertSame($fk, $result);

        // Verify the first mutation occurred
        static::assertSame('xxxx', $fk->getName());

        // Second mutation to verify mutability
        $fk->setName('yyyy');

        // Verify the instance was actually mutated
        static::assertSame('yyyy', $fk->getName());
    }

    #[Test]
    public function setOnDeleteRule(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setOnDeleteRule('CASCADE');

        // Verify fluent interface
        static::assertSame($fk, $result);

        // Verify the first mutation occurred
        static::assertSame('CASCADE', $fk->getOnDeleteRule());

        // Second mutation to verify mutability
        $fk->setOnDeleteRule('SET NULL');

        // Verify the instance was actually mutated
        static::assertSame('SET NULL', $fk->getOnDeleteRule());
    }

    #[Test]
    public function setOnUpdateRule(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setOnUpdateRule('CASCADE');

        // Verify fluent interface
        static::assertSame($fk, $result);

        // Verify the first mutation occurred
        static::assertSame('CASCADE', $fk->getOnUpdateRule());

        // Second mutation to verify mutability
        $fk->setOnUpdateRule('RESTRICT');

        // Verify the instance was actually mutated
        static::assertSame('RESTRICT', $fk->getOnUpdateRule());
    }

    #[Test]
    public function setReferenceColumn(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setReferenceColumn('xxxx');

        // Verify fluent interface
        static::assertSame($fk, $result);

        // Verify the first mutation occurred
        static::assertEquals(['xxxx'], $fk->getReferenceColumn());

        // Second mutation to verify mutability
        $fk->setReferenceColumn('yyyy');

        // Verify the instance was actually mutated
        static::assertEquals(['yyyy'], $fk->getReferenceColumn());
    }

    #[Test]
    public function setReferenceTable(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');

        // First mutation
        $result = $fk->setReferenceTable('xxxx');

        // Verify fluent interface
        static::assertSame($fk, $result);

        // Verify the first mutation occurred
        static::assertSame('xxxx', $fk->getReferenceTable());

        // Second mutation to verify mutability
        $fk->setReferenceTable('yyyy');

        // Verify the instance was actually mutated
        static::assertSame('yyyy', $fk->getReferenceTable());
    }
}
