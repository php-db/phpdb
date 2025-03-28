<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Adapter\Platform\SqlServer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

use function restore_error_handler;
use function set_error_handler;

#[CoversMethod(SqlServer::class, 'getName')]
#[CoversMethod(SqlServer::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(SqlServer::class, 'quoteIdentifier')]
#[CoversMethod(SqlServer::class, 'quoteIdentifierChain')]
#[CoversMethod(SqlServer::class, 'getQuoteValueSymbol')]
#[CoversMethod(SqlServer::class, 'quoteValue')]
#[CoversMethod(SqlServer::class, 'quoteTrustedValue')]
#[CoversMethod(SqlServer::class, 'quoteValueList')]
#[CoversMethod(SqlServer::class, 'getIdentifierSeparator')]
#[CoversMethod(SqlServer::class, 'quoteIdentifierInFragment')]
#[CoversMethod(SqlServer::class, 'setDriver')]
class SqlServerTest extends TestCase
{
    /** @var SqlServer */
    protected $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->platform = new SqlServer();
    }

    public function testGetName()
    {
        self::assertEquals('SQLServer', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol()
    {
        self::assertEquals(['[', ']'], $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier()
    {
        self::assertEquals('[identifier]', $this->platform->quoteIdentifier('identifier'));
    }

    public function testQuoteIdentifierChain()
    {
        self::assertEquals('[identifier]', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('[identifier]', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('[schema].[identifier]', $this->platform->quoteIdentifierChain(['schema', 'identifier']));
    }

    public function testGetQuoteValueSymbol()
    {
        self::assertEquals("'", $this->platform->getQuoteValueSymbol());
    }

    public function testQuoteValueRaisesNoticeWithoutPlatformSupport()
    {
        /**
         * @todo Determine if vulnerability warning is required during unit testing
         */
        //$this->expectNotice();
        //$this->expectExceptionMessage(
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\SqlServer without extension/driver support can '
        //    . 'introduce security vulnerabilities in a production environment'
        //);
        $this->expectNotToPerformAssertions();
        $this->platform->quoteValue('value');
    }

    public function testQuoteValue()
    {
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O''Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            "'''; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "'\\''; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteTrustedValue()
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            "'''; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "'\\''; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteValueList()
    {
        /**
         * @todo Determine if vulnerability warning is required during unit testing
         */
        //$this->expectError();
        //$this->expectExceptionMessage(
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\SqlServer without extension/driver support can '
        //    . 'introduce security vulnerabilities in a production environment'
        //);
        self::assertEquals("'Foo O''Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    public function testGetIdentifierSeparator()
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());
    }

    public function testQuoteIdentifierInFragment()
    {
        self::assertEquals('[foo].[bar]', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('[foo] as [bar]', $this->platform->quoteIdentifierInFragment('foo as bar'));

        // single char words
        self::assertEquals(
            '([foo].[bar] = [boo].[baz])',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '='])
        );

        // case insensitive safe words
        self::assertEquals(
            '([foo].[bar] = [boo].[baz]) AND ([foo].[baz] = [boo].[baz])',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and']
            )
        );

        // case insensitive safe words in field
        self::assertEquals(
            '([foo].[bar] = [boo].baz) AND ([foo].baz = [boo].baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz']
            )
        );
    }

    public function testSetDriver()
    {
        $this->expectNotToPerformAssertions();
        $driver = new Pdo(['pdodriver' => 'sqlsrv']);
        $this->platform->setDriver($driver);
    }

    public function testPlatformQuotesNullByteCharacter()
    {
        set_error_handler(function () {
        });
        $string = "1\0";
        $value  = $this->platform->quoteValue($string);
        restore_error_handler();
        self::assertEquals("'1\\000'", $value);
    }
}
