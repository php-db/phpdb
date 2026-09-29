<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Platform;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Adapter\Platform\Sql92;
use PhpDbTest\Adapter\Platform\TestAsset\TestPlatform;
use PhpDbTest\TestAsset\TestSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function abstractPlatformQuoteValueEscapesWithDriver(): void
    {
        $platform = new TestPlatform($this->createStub(DriverInterface::class));

        static::assertSame("'test\\'value'", $platform->quoteValue("test'value"));
    }

    #[Test]
    public function abstractPlatformQuoteValueThrowsWithoutDriver(): void
    {
        $platform = new TestPlatform();

        $this->expectException(RuntimeException::class);
        $platform->quoteValue('value');
    }

    #[Test]
    public function getIdentifierSeparator(): void
    {
        static::assertSame('.', $this->platform->getIdentifierSeparator());
    }

    #[Test]
    public function getName(): void
    {
        static::assertSame('SQL92', $this->platform->getName());
    }

    #[Test]
    public function getQuoteIdentifierSymbol(): void
    {
        static::assertSame('"', $this->platform->getQuoteIdentifierSymbol());
    }

    #[Test]
    public function getQuoteValueSymbol(): void
    {
        static::assertSame("'", $this->platform->getQuoteValueSymbol());
    }

    #[Test]
    public function quoteIdentifier(): void
    {
        static::assertSame('"identifier"', $this->platform->quoteIdentifier('identifier'));
    }

    #[Test]
    public function quoteIdentifierChain(): void
    {
        static::assertSame('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        static::assertSame('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        static::assertSame('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));
    }

    #[Test]
    public function quoteIdentifierInFragment(): void
    {
        static::assertSame('"foo"."bar"', $this->platform->quoteIdentifierInFragment('foo.bar'));
        static::assertSame('"foo" as "bar"', $this->platform->quoteIdentifierInFragment('foo as bar'));

        // single char words
        static::assertSame(
            '("foo"."bar" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '=']),
        );

        // case insensitive safe words
        static::assertSame(
            '("foo"."bar" = "boo"."baz") AND ("foo"."baz" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and'],
            ),
        );

        // case insensitive safe words in field
        static::assertSame(
            '("foo"."bar" = "boo".baz) AND ("foo".baz = "boo".baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz'],
            ),
        );
    }

    #[Test]
    public function quoteIdentifierInFragmentReturnsUnquotedWhenQuotingDisabled(): void
    {
        $platform = new TestSql92Platform(quoteIdentifiers: false);

        static::assertSame('foo.bar', $platform->quoteIdentifierInFragment('foo.bar'));
    }

    #[Test]
    public function quoteIdentifierReturnsUnquotedWhenQuotingDisabled(): void
    {
        $platform = new TestSql92Platform(quoteIdentifiers: false);

        static::assertSame('test', $platform->quoteIdentifier('test'));
    }

    #[Test]
    public function quoteTrustedValueEscapesSpecialCharacters(): void
    {
        static::assertSame("'value'", $this->platform->quoteTrustedValue('value'));
        static::assertSame("'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        static::assertSame(
            '\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue("'; DELETE FROM some_table; -- "),
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        static::assertSame(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- '),
        );
    }

    #[Test]
    public function quoteValueEscapesSpecialCharacters(): void
    {
        $platform = new TestSql92Platform(driver: $this->createStub(DriverInterface::class));

        $quoted = $platform->quoteValue("test'value");

        static::assertStringContainsString('test', $quoted);
        static::assertStringStartsWith("'", $quoted);
        static::assertStringEndsWith("'", $quoted);
    }

    #[Test]
    public function quoteValueListThrowsWithoutDriver(): void
    {
        $this->expectException(RuntimeException::class);
        static::assertSame("'Foo O\\'Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    #[Test]
    public function quoteValueRaisesNoticeWithoutPlatformSupport(): void
    {
        $this->expectException(RuntimeException::class);
        $this->platform->quoteValue('value');
    }

    #[Test]
    public function quoteValueThrowsWithoutDriver(): void
    {
        $this->expectException(RuntimeException::class);
        static::assertSame("'value'", @$this->platform->quoteValue('value'));
        static::assertSame("'Foo O\\'Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        static::assertSame(
            '\'\\\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue("'; DELETE FROM some_table; -- "),
        );
        static::assertSame(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- '),
        );
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->platform = new Sql92();
    }
}
