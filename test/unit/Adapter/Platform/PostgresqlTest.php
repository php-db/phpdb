<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Platform\Postgresql;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Postgresql::class, 'getName')]
#[CoversMethod(Postgresql::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(Postgresql::class, 'quoteIdentifier')]
#[CoversMethod(Postgresql::class, 'quoteIdentifierChain')]
#[CoversMethod(Postgresql::class, 'getQuoteValueSymbol')]
#[CoversMethod(Postgresql::class, 'quoteValue')]
#[CoversMethod(Postgresql::class, 'quoteTrustedValue')]
#[CoversMethod(Postgresql::class, 'quoteValueList')]
#[CoversMethod(Postgresql::class, 'getIdentifierSeparator')]
#[CoversMethod(Postgresql::class, 'quoteIdentifierInFragment')]
class PostgresqlTest extends TestCase
{
    protected Postgresql $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->platform = new Postgresql();
    }

    public function testGetName(): void
    {
        self::assertEquals('PostgreSQL', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol(): void
    {
        self::assertEquals('"', $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifier('identifier'));
        self::assertEquals(
            '"identifier ""with"" double-quotes"',
            $this->platform->quoteIdentifier('identifier "with" double-quotes')
        );
    }

    public function testQuoteIdentifierChain(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));
        self::assertEquals(
            '"schema"."identifier ""with"" double-quotes"',
            $this->platform->quoteIdentifierChain(['schema', 'identifier "with" double-quotes'])
        );
    }

    public function testGetQuoteValueSymbol(): void
    {
        self::assertEquals("'", $this->platform->getQuoteValueSymbol());
    }

    public function testQuoteValueRaisesNoticeWithoutPlatformSupport(): void
    {
        /**
         * @todo Determine if vulnerability warning is required during unit testing
         */
        //$this->expectNotice();
        //$this->expectExceptionMessage(
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Postgresql without extension/driver'
        //    . ' support can introduce security vulnerabilities in a production environment'
        //);
        $this->expectNotToPerformAssertions();
        $this->platform->quoteValue('value');
    }

    public function testQuoteValue(): void
    {
        self::assertEquals("E'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("E'Foo O\\'Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            'E\'\\\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "E'\\\\\\'; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteTrustedValue(): void
    {
        self::assertEquals("E'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("E'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            'E\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- ')
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        self::assertEquals(
            "E'\\\\\\'; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteValueList(): void
    {
        /**
         * @todo Determine if vulnerability warning is required during unit testing
         */
        //$this->expectError();
        //$this->expectExceptionMessage(
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Postgresql without extension/driver'
        //    . ' support can introduce security vulnerabilities in a production environment'
        //);
        $fooOBar = $this->platform->quoteTrustedValue("Foo O'Bar");
        self::assertEquals($fooOBar, $this->platform->quoteValueList("Foo O'Bar"));
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
}
