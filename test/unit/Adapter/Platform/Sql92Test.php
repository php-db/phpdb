<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Platform;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Adapter\Platform\Sql92;
use PhpDbTest\Adapter\Platform\TestAsset\TestPlatform;
use PhpDbTest\TestAsset\TestSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Sql92::class, 'getName')]
#[CoversMethod(Sql92::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(Sql92::class, 'quoteIdentifier')]
#[CoversMethod(Sql92::class, 'quoteIdentifierChain')]
#[CoversMethod(Sql92::class, 'getQuoteValueSymbol')]
#[CoversMethod(Sql92::class, 'quoteValue')]
#[CoversMethod(Sql92::class, 'quoteTrustedValue')]
#[CoversMethod(Sql92::class, 'quoteValueList')]
#[CoversMethod(Sql92::class, 'getIdentifierSeparator')]
#[CoversMethod(Sql92::class, 'quoteIdentifierInFragment')]
#[CoversMethod(AbstractPlatform::class, 'quoteIdentifier')]
#[CoversMethod(AbstractPlatform::class, 'quoteIdentifierInFragment')]
#[CoversMethod(AbstractPlatform::class, 'quoteValue')]
#[CoversMethod(AbstractPlatform::class, 'quoteIdentifierChain')]
#[CoversMethod(AbstractPlatform::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(AbstractPlatform::class, 'getQuoteValueSymbol')]
#[CoversMethod(AbstractPlatform::class, 'quoteTrustedValue')]
#[CoversMethod(AbstractPlatform::class, 'quoteValueList')]
#[CoversMethod(AbstractPlatform::class, 'getIdentifierSeparator')]
#[Group('unit')]
final class Sql92Test extends TestCase
{
    protected Sql92 $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->platform = new Sql92();
    }

    public function testGetName(): void
    {
        self::assertEquals('SQL92', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol(): void
    {
        self::assertEquals('"', $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifier('identifier'));
    }

    public function testQuoteIdentifierChain(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));
    }

    public function testGetQuoteValueSymbol(): void
    {
        self::assertEquals("'", $this->platform->getQuoteValueSymbol());
    }

    public function testQuoteValueRaisesNoticeWithoutPlatformSupport(): void
    {
        $this->expectException(VunerablePlatformQuoteException::class);
        $this->platform->quoteValue('value');
    }

    public function testQuoteValueThrowsWithoutDriver(): void
    {
        $this->expectException(VunerablePlatformQuoteException::class);
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O\\'Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue("'; DELETE FROM some_table; -- ")
        );
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteTrustedValueEscapesSpecialCharacters(): void
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue("'; DELETE FROM some_table; -- ")
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteValueListThrowsWithoutDriver(): void
    {
        $this->expectException(VunerablePlatformQuoteException::class);
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    public function testGetIdentifierSeparator(): void
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());
    }

    public function testQuoteIdentifierInFragment(): void
    {
        self::assertEquals('"foo"."bar"', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('"foo" as "bar"', $this->platform->quoteIdentifierInFragment('foo as bar'));

        // single char words
        self::assertEquals(
            '("foo"."bar" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '='])
        );

        // case insensitive safe words
        self::assertEquals(
            '("foo"."bar" = "boo"."baz") AND ("foo"."baz" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and']
            )
        );

        // case insensitive safe words in field
        self::assertEquals(
            '("foo"."bar" = "boo".baz) AND ("foo".baz = "boo".baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz']
            )
        );
    }

    public function testQuoteIdentifierReturnsUnquotedWhenQuotingDisabled(): void
    {
        $platform = new TestSql92Platform(quoteIdentifiers: false);

        self::assertSame('test', $platform->quoteIdentifier('test'));
    }

    public function testQuoteIdentifierInFragmentReturnsUnquotedWhenQuotingDisabled(): void
    {
        $platform = new TestSql92Platform(quoteIdentifiers: false);

        self::assertSame('foo.bar', $platform->quoteIdentifierInFragment('foo.bar'));
    }

    public function testQuoteValueEscapesSpecialCharacters(): void
    {
        $platform = new TestSql92Platform(driver: $this->createStub(DriverInterface::class));

        $quoted = $platform->quoteValue("test'value");

        self::assertStringContainsString('test', $quoted);
        self::assertStringStartsWith("'", $quoted);
        self::assertStringEndsWith("'", $quoted);
    }

    public function testAbstractPlatformQuoteValueThrowsWithoutDriver(): void
    {
        $platform = new TestPlatform();

        $this->expectException(VunerablePlatformQuoteException::class);
        $platform->quoteValue('value');
    }

    public function testAbstractPlatformQuoteValueEscapesWithDriver(): void
    {
        $platform = new TestPlatform($this->createStub(DriverInterface::class));

        self::assertSame("'test\\'value'", $platform->quoteValue("test'value"));
    }
}
