<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Platform\IbmDb2;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(IbmDb2::class, 'getName')]
#[CoversMethod(IbmDb2::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(IbmDb2::class, 'quoteIdentifier')]
#[CoversMethod(IbmDb2::class, 'quoteIdentifierChain')]
#[CoversMethod(IbmDb2::class, 'getQuoteValueSymbol')]
#[CoversMethod(IbmDb2::class, 'quoteValue')]
#[CoversMethod(IbmDb2::class, 'quoteTrustedValue')]
#[CoversMethod(IbmDb2::class, 'quoteValueList')]
#[CoversMethod(IbmDb2::class, 'getIdentifierSeparator')]
#[CoversMethod(IbmDb2::class, 'quoteIdentifierInFragment')]
class IbmDb2Test extends TestCase
{
    protected IbmDb2 $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->platform = new IbmDb2();
    }

    public function testGetName(): void
    {
        self::assertEquals('IBM DB2', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol(): void
    {
        self::assertEquals('"', $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifier('identifier'));

        $platform = new IbmDb2(['quote_identifiers' => false]);
        self::assertEquals('identifier', $platform->quoteIdentifier('identifier'));
    }

    public function testQuoteIdentifierChain(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));

        $platform = new IbmDb2(['quote_identifiers' => false]);
        self::assertEquals('identifier', $platform->quoteIdentifierChain('identifier'));
        self::assertEquals('identifier', $platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('schema.identifier', $platform->quoteIdentifierChain(['schema', 'identifier']));

        $platform = new IbmDb2(['identifier_separator' => '\\']);
        self::assertEquals('"schema"\"identifier"', $platform->quoteIdentifierChain(['schema', 'identifier']));
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
        //if (! function_exists('db2_escape_string')) {
            //$this->expectNotice();
            //$this->expectExceptionMessage(
            //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\IbmDb2 without extension/driver'
            //    . ' support can introduce security vulnerabilities in a production environment'
            //);
        //}
        $this->expectNotToPerformAssertions();
        $this->platform->quoteValue('value');
    }

    public function testQuoteValue(): void
    {
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O''Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            "'''; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue("'; DELETE FROM some_table; -- ")
        );
        self::assertEquals(
            "'\\''; \nDELETE FROM some_table; -- '",
            @$this->platform->quoteValue("\\'; \nDELETE FROM some_table; -- ")
        );
    }

    public function testQuoteTrustedValue(): void
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            "'''; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue("'; DELETE FROM some_table; -- ")
        );
        self::assertEquals(
            "'\\''; \nDELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue("\\'; \nDELETE FROM some_table; -- ")
        );
    }

    public function testQuoteValueList(): void
    {
        /**
         * @todo Determine if vulnerability warning is required during unit testing
         */
        //if (! function_exists('db2_escape_string')) {
            //$this->expectError();
            //$this->expectExceptionMessage(
            //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\IbmDb2 without extension/driver'
            //    . ' support can introduce security vulnerabilities in a production environment'
            //);
        //}
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    public function testGetIdentifierSeparator(): void
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());

        $platform = new IbmDb2(['identifier_separator' => '\\']);
        self::assertEquals('\\', $platform->getIdentifierSeparator());
    }

    public function testQuoteIdentifierInFragment(): void
    {
        self::assertEquals('"foo"."bar"', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('"foo" as "bar"', $this->platform->quoteIdentifierInFragment('foo as bar'));

        $platform = new IbmDb2(['quote_identifiers' => false]);
        self::assertEquals('foo.bar', $platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('foo as bar', $platform->quoteIdentifierInFragment('foo as bar'));

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
