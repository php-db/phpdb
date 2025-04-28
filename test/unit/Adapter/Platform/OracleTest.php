<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Driver\Oci8\Oci8;
use Laminas\Db\Adapter\Exception\InvalidArgumentException;
use Laminas\Db\Adapter\Platform\Oracle;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Oracle::class, '__construct')]
#[CoversMethod(Oracle::class, 'setDriver')]
#[CoversMethod(Oracle::class, 'getDriver')]
#[CoversMethod(Oracle::class, 'getName')]
#[CoversMethod(Oracle::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(Oracle::class, 'quoteIdentifier')]
#[CoversMethod(Oracle::class, 'quoteIdentifierChain')]
#[CoversMethod(Oracle::class, 'getQuoteValueSymbol')]
#[CoversMethod(Oracle::class, 'quoteValue')]
#[CoversMethod(Oracle::class, 'quoteTrustedValue')]
#[CoversMethod(Oracle::class, 'quoteValueList')]
#[CoversMethod(Oracle::class, 'getIdentifierSeparator')]
#[CoversMethod(Oracle::class, 'quoteIdentifierInFragment')]
class OracleTest extends TestCase
{
    protected Oracle $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->platform = new Oracle();
    }

    public function testContructWithOptions(): void
    {
        self::assertEquals('"\'test\'.\'test\'"', $this->platform->quoteIdentifier('"test"."test"'));
        $plataform1 = new Oracle(['quote_identifiers' => false]);
        self::assertEquals('"test"."test"', $plataform1->quoteIdentifier('"test"."test"'));
        $plataform2 = new Oracle(['quote_identifiers' => 'false']);
        self::assertEquals('"test"."test"', $plataform2->quoteIdentifier('"test"."test"'));
    }

    /**
     * @throws Exception
     */
    public function testContructWithDriver(): void
    {
        $mockDriver = $this->getMockBuilder(Oci8::class)->setConstructorArgs([[]])->onlyMethods([])->getMock();
        $platform   = new Oracle([], $mockDriver);
        self::assertEquals($mockDriver, $platform->getDriver());
    }

    /**
     * @throws Exception
     */
    public function testSetDriver(): void
    {
        $mockDriver = $this->getMockBuilder(Oci8::class)->setConstructorArgs([[]])->onlyMethods([])->getMock();
        $platform   = $this->platform->setDriver($mockDriver);
        self::assertEquals($mockDriver, $platform->getDriver());
    }

    public function testSetDriverInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '$driver must be a Oci8 or Oracle PDO Laminas\Db\Adapter\Driver, Oci8 instance, or Oci PDO instance'
        );
        /** @psalm-suppress NullArgument - ensure an exception is thrown */
        $this->platform->setDriver(null);
    }

    public function testGetDriver(): void
    {
        self::assertNull($this->platform->getDriver());
    }

    public function testGetName(): void
    {
        self::assertEquals('Oracle', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol(): void
    {
        self::assertEquals('"', $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifier('identifier'));

        $platform = new Oracle(['quote_identifiers' => false]);
        self::assertEquals('identifier', $platform->quoteIdentifier('identifier'));
    }

    public function testQuoteIdentifierChain(): void
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));

        $platform = new Oracle(['quote_identifiers' => false]);
        self::assertEquals('identifier', $platform->quoteIdentifierChain('identifier'));
        self::assertEquals('identifier', $platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('schema.identifier', $platform->quoteIdentifierChain(['schema', 'identifier']));
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
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Oracle without '
        //    . 'extension/driver support can introduce security vulnerabilities in a production environment'
        //);
        $this->expectNotToPerformAssertions();
        $this->platform->quoteValue('value');
    }

    public function testQuoteValue(): void
    {
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O''Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            '\'\'\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "'\\''; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteTrustedValue(): void
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            '\'\'\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- ')
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        self::assertEquals(
            "'\\''; DELETE FROM some_table; -- '",
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
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Oracle without '
        //    . 'extension/driver support can introduce security vulnerabilities in a production environment'
        //);
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    public function testGetIdentifierSeparator(): void
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());
    }

    public function testQuoteIdentifierInFragment(): void
    {
        self::assertEquals('"foo"."bar"', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('"foo" as "bar"', $this->platform->quoteIdentifierInFragment('foo as bar'));

        $platform = new Oracle(['quote_identifiers' => false]);
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
