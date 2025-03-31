<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Platform\Mysql;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Mysql::class, 'getName')]
#[CoversMethod(Mysql::class, 'getQuoteIdentifierSymbol')]
#[CoversMethod(Mysql::class, 'quoteIdentifier')]
#[CoversMethod(Mysql::class, 'quoteIdentifierChain')]
#[CoversMethod(Mysql::class, 'getQuoteValueSymbol')]
#[CoversMethod(Mysql::class, 'quoteValue')]
#[CoversMethod(Mysql::class, 'quoteTrustedValue')]
#[CoversMethod(Mysql::class, 'quoteValueList')]
#[CoversMethod(Mysql::class, 'getIdentifierSeparator')]
#[CoversMethod(Mysql::class, 'quoteIdentifierInFragment')]
final class MysqlTest extends TestCase
{
    protected Mysql $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->platform = new Mysql();
    }

    public function testGetName(): void
    {
        self::assertEquals('MySQL', $this->platform->getName());
    }

    public function testGetQuoteIdentifierSymbol(): void
    {
        self::assertEquals('`', $this->platform->getQuoteIdentifierSymbol());
    }

    public function testQuoteIdentifier(): void
    {
        self::assertEquals('`identifier`', $this->platform->quoteIdentifier('identifier'));
        self::assertEquals('`ident``ifier`', $this->platform->quoteIdentifier('ident`ifier'));
        self::assertEquals('`namespace:$identifier`', $this->platform->quoteIdentifier('namespace:$identifier'));
    }

    public function testQuoteIdentifierChain(): void
    {
        self::assertEquals('`identifier`', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('`identifier`', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('`schema`.`identifier`', $this->platform->quoteIdentifierChain(['schema', 'identifier']));

        self::assertEquals('`ident``ifier`', $this->platform->quoteIdentifierChain('ident`ifier'));
        self::assertEquals('`ident``ifier`', $this->platform->quoteIdentifierChain(['ident`ifier']));
        self::assertEquals(
            '`schema`.`ident``ifier`',
            $this->platform->quoteIdentifierChain(['schema', 'ident`ifier'])
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
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Mysql without extension/driver support can '
        //    . 'introduce security vulnerabilities in a production environment'
        //);
        $this->expectNotToPerformAssertions();
        $this->platform->quoteValue('value');
    }

    public function testQuoteValue(): void
    {
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O\\'Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    public function testQuoteTrustedValue(): void
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- ')
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
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
        //    'Attempting to quote a value in Laminas\Db\Adapter\Platform\Mysql without extension/driver support can '
        //    . 'introduce security vulnerabilities in a production environment'
        //);
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    public function testGetIdentifierSeparator(): void
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());
    }

    public function testQuoteIdentifierInFragment(): void
    {
        self::assertEquals('`foo`.`bar`', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('`foo` as `bar`', $this->platform->quoteIdentifierInFragment('foo as bar'));
        self::assertEquals('`$TableName`.`bar`', $this->platform->quoteIdentifierInFragment('$TableName.bar'));
        self::assertEquals(
            '`cmis:$TableName` as `cmis:TableAlias`',
            $this->platform->quoteIdentifierInFragment('cmis:$TableName as cmis:TableAlias')
        );

        $this->assertEquals(
            '`foo-bar`.`bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar.bar-foo')
        );
        $this->assertEquals(
            '`foo-bar` as `bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar as bar-foo')
        );
        $this->assertEquals(
            '`$TableName-$ColumnName`.`bar-foo`',
            $this->platform->quoteIdentifierInFragment('$TableName-$ColumnName.bar-foo')
        );
        $this->assertEquals(
            '`cmis:$TableName-$ColumnName` as `cmis:TableAlias-ColumnAlias`',
            $this->platform->quoteIdentifierInFragment('cmis:$TableName-$ColumnName as cmis:TableAlias-ColumnAlias')
        );

        // single char words
        self::assertEquals(
            '(`foo`.`bar` = `boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '='])
        );
        self::assertEquals(
            '(`foo`.`bar`=`boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment('(foo.bar=boo.baz)', ['(', ')', '='])
        );
        self::assertEquals('`foo`=`bar`', $this->platform->quoteIdentifierInFragment('foo=bar', ['=']));

        $this->assertEquals(
            '(`foo-bar`.`bar-foo` = `boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment('(foo-bar.bar-foo = boo-baz.baz-boo)', ['(', ')', '='])
        );
        $this->assertEquals(
            '(`foo-bar`.`bar-foo`=`boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment('(foo-bar.bar-foo=boo-baz.baz-boo)', ['(', ')', '='])
        );
        $this->assertEquals(
            '`foo-bar`=`bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar=bar-foo', ['='])
        );

        // case insensitive safe words
        self::assertEquals(
            '(`foo`.`bar` = `boo`.`baz`) AND (`foo`.`baz` = `boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and']
            )
        );

        $this->assertEquals(
            '(`foo-bar`.`bar-foo` = `boo-baz`.`baz-boo`) AND (`foo-baz`.`baz-foo` = `boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment(
                '(foo-bar.bar-foo = boo-baz.baz-boo) AND (foo-baz.baz-foo = boo-baz.baz-boo)',
                ['(', ')', '=', 'and']
            )
        );

        // case insensitive safe words in field
        self::assertEquals(
            '(`foo`.`bar` = `boo`.baz) AND (`foo`.baz = `boo`.baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz']
            )
        );

        // case insensitive safe words in field
        $this->assertEquals(
            '(`foo-bar`.`bar-foo` = `boo-baz`.baz-boo) AND (`foo-baz`.`baz-foo` = `boo-baz`.baz-boo)',
            $this->platform->quoteIdentifierInFragment(
                '(foo-bar.bar-foo = boo-baz.baz-boo) AND (foo-baz.baz-foo = boo-baz.baz-boo)',
                ['(', ')', '=', 'and', 'bAz-BOo']
            )
        );
    }
}
