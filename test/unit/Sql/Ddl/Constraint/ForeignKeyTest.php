<?php

namespace PhpDbTest\Sql\Ddl\Constraint;

use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

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
    public function testSetName(): ForeignKey
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');
        self::assertSame($fk, $fk->setName('xxxx'));
        return $fk;
    }

    #[Depends('testSetName')]
    public function testGetName(ForeignKey $fk): void
    {
        self::assertEquals('xxxx', $fk->getName());
    }

    public function testSetReferenceTable(): ForeignKey
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');
        self::assertSame($fk, $fk->setReferenceTable('xxxx'));
        return $fk;
    }

    #[Depends('testSetReferenceTable')]
    public function testGetReferenceTable(ForeignKey $fk): void
    {
        self::assertEquals('xxxx', $fk->getReferenceTable());
    }

    public function testSetReferenceColumn(): ForeignKey
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');
        self::assertSame($fk, $fk->setReferenceColumn('xxxx'));
        return $fk;
    }

    #[Depends('testSetReferenceColumn')]
    public function testGetReferenceColumn(ForeignKey $fk): void
    {
        self::assertEquals(['xxxx'], $fk->getReferenceColumn());
    }

    public function testSetOnDeleteRule(): ForeignKey
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');
        self::assertSame($fk, $fk->setOnDeleteRule('CASCADE'));
        return $fk;
    }

    #[Depends('testSetOnDeleteRule')]
    public function testGetOnDeleteRule(ForeignKey $fk): void
    {
        self::assertEquals('CASCADE', $fk->getOnDeleteRule());
    }

    public function testSetOnUpdateRule(): ForeignKey
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam');
        self::assertSame($fk, $fk->setOnUpdateRule('CASCADE'));
        return $fk;
    }

    #[Depends('testSetOnUpdateRule')]
    public function testGetOnUpdateRule(ForeignKey $fk): void
    {
        self::assertEquals('CASCADE', $fk->getOnUpdateRule());
    }

    public function testGetExpressionData(): void
    {
        $fk = new ForeignKey('foo', 'bar', 'baz', 'bam', 'CASCADE', 'SET NULL');
        self::assertEquals(
            [
                [
                    'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                    ['foo', 'bar', 'baz', 'bam', 'CASCADE', 'SET NULL'],
                    [
                        $fk::TYPE_IDENTIFIER,
                        $fk::TYPE_IDENTIFIER,
                        $fk::TYPE_IDENTIFIER,
                        $fk::TYPE_IDENTIFIER,
                        $fk::TYPE_LITERAL,
                        $fk::TYPE_LITERAL,
                    ],
                ],
            ],
            $fk->getExpressionData()
        );
    }
}
