<?php

namespace LaminasTest\Db\Adapter;

use Laminas\Db\Adapter\ParameterContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversMethod(ParameterContainer::class, 'offsetExists')]
#[CoversMethod(ParameterContainer::class, 'offsetGet')]
#[CoversMethod(ParameterContainer::class, 'offsetSet')]
#[CoversMethod(ParameterContainer::class, 'offsetUnset')]
#[CoversMethod(ParameterContainer::class, 'setFromArray')]
#[CoversMethod(ParameterContainer::class, 'offsetSetMaxLength')]
#[CoversMethod(ParameterContainer::class, 'offsetGetMaxLength')]
#[CoversMethod(ParameterContainer::class, 'offsetHasMaxLength')]
#[CoversMethod(ParameterContainer::class, 'offsetUnsetMaxLength')]
#[CoversMethod(ParameterContainer::class, 'getMaxLengthIterator')]
#[CoversMethod(ParameterContainer::class, 'offsetSetErrata')]
#[CoversMethod(ParameterContainer::class, 'offsetGetErrata')]
#[CoversMethod(ParameterContainer::class, 'offsetHasErrata')]
#[CoversMethod(ParameterContainer::class, 'offsetUnsetErrata')]
#[CoversMethod(ParameterContainer::class, 'getErrataIterator')]
#[CoversMethod(ParameterContainer::class, 'getNamedArray')]
#[CoversMethod(ParameterContainer::class, 'count')]
#[CoversMethod(ParameterContainer::class, 'current')]
#[CoversMethod(ParameterContainer::class, 'next')]
#[CoversMethod(ParameterContainer::class, 'key')]
#[CoversMethod(ParameterContainer::class, 'valid')]
#[CoversMethod(ParameterContainer::class, 'rewind')]
class ParameterContainerTest extends TestCase
{
    protected ParameterContainer $parameterContainer;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->parameterContainer = new ParameterContainer(['foo' => 'bar']);
    }

    #[TestDox('unit test: Test offsetExists() returns proper values via method call and isset()')]
    public function testOffsetExists(): void
    {
        self::assertTrue($this->parameterContainer->offsetExists('foo'));
        self::assertTrue(isset($this->parameterContainer['foo']));
        self::assertFalse($this->parameterContainer->offsetExists('bar'));
        self::assertFalse(isset($this->parameterContainer['bar']));
    }

    #[TestDox('unit test: Test offsetGet() returns proper values via method call and array access')]
    public function testOffsetGet(): void
    {
        self::assertEquals('bar', $this->parameterContainer->offsetGet('foo'));
        self::assertEquals('bar', $this->parameterContainer['foo']);

        self::assertNull($this->parameterContainer->offsetGet('bar'));
        // @todo determine what should come back here
    }

    #[TestDox('unit test: Test offsetSet() works via method call and array access')]
    public function testOffsetSet(): void
    {
        $this->parameterContainer->offsetSet('boo', 'baz');
        self::assertEquals('baz', $this->parameterContainer->offsetGet('boo'));

        $this->parameterContainer->offsetSet('1', 'book', ParameterContainer::TYPE_STRING, 4);
        self::assertEquals(
            ['foo' => 'bar', 'boo' => 'baz', '1' => 'book'],
            $this->parameterContainer->getNamedArray()
        );

        self::assertEquals('string', $this->parameterContainer->offsetGetErrata('1'));
        self::assertEquals(4, $this->parameterContainer->offsetGetMaxLength('1'));

        // test that setting an index applies to correct named parameter
        $this->parameterContainer[0] = 'Zero';
        $this->parameterContainer[1] = 'One';
        self::assertEquals(
            ['foo' => 'Zero', 'boo' => 'One', '1' => 'book'],
            $this->parameterContainer->getNamedArray()
        );
        self::assertEquals(
            [0 => 'Zero', 1 => 'One', 2 => 'book'],
            $this->parameterContainer->getPositionalArray()
        );

        // test no-index applies
        $this->parameterContainer['buffer'] = 'A buffer Element';
        $this->parameterContainer[]         = 'Second To Last';
        $this->parameterContainer[]         = 'Last';
        self::assertEquals(
            [
                'foo'    => 'Zero',
                'boo'    => 'One',
                '1'      => 'book',
                'buffer' => 'A buffer Element',
                '4'      => 'Second To Last',
                '5'      => 'Last',
            ],
            $this->parameterContainer->getNamedArray()
        );
        self::assertEquals(
            [0 => 'Zero', 1 => 'One', 2 => 'book', 3 => 'A buffer Element', 4 => 'Second To Last', 5 => 'Last'],
            $this->parameterContainer->getPositionalArray()
        );
    }

    #[TestDox('unit test: Test offsetUnset() works via method call and array access')]
    public function testOffsetUnset(): void
    {
        $this->parameterContainer->offsetSet('boo', 'baz');
        self::assertTrue($this->parameterContainer->offsetExists('boo'));

        $this->parameterContainer->offsetUnset('boo');
        self::assertFalse($this->parameterContainer->offsetExists('boo'));
    }

    #[TestDox('unit test: Test setFromArray() will populate the container')]
    public function testSetFromArray(): void
    {
        $this->parameterContainer->setFromArray(['bar' => 'baz']);
        self::assertEquals('baz', $this->parameterContainer['bar']);
    }

    /**
     * Handle statement parameters - https://github.com/laminas/laminas-db/issues/47
     *
     * @see Insert::procesInsert as example
     */
    public function testSetFromArrayNamed(): void
    {
        $this->parameterContainer->offsetSet('c_0', ':myparam');
        $this->parameterContainer->setFromArray([':myparam' => 'baz']);
        self::assertEquals('baz', $this->parameterContainer['c_0']);
        self::assertEquals('baz', $this->parameterContainer[':myparam']);
    }

    #[TestDox('
        unit test: Test offsetSetMaxLength() will persist errata data
        unit test: Test offsetGetMaxLength() return persisted errata data, if it exists
    ')]
    public function testOffsetSetAndGetMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        self::assertEquals(100, $this->parameterContainer->offsetGetMaxLength('foo'));
    }

    #[TestDox('unit test: Test offsetHasMaxLength() will check if errata exists for a particular key')]
    public function testOffsetHasMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        self::assertTrue($this->parameterContainer->offsetHasMaxLength('foo'));
    }

    #[TestDox('unit test: Test offsetUnsetMaxLength() will unset data for a particular key')]
    public function testOffsetUnsetMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        $this->parameterContainer->offsetUnsetMaxLength('foo');
        self::assertNull($this->parameterContainer->offsetGetMaxLength('foo'));
    }

    #[TestDox('unit test: Test getMaxLengthIterator() will return an iterator for the errata data')]
    public function testGetMaxLengthIterator(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        $data = $this->parameterContainer->getMaxLengthIterator();
        self::assertInstanceOf('ArrayIterator', $data);
    }

    #[TestDox('unit test: Test offsetSetErrata() will persist errata data')]
    public function testOffsetSetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        self::assertEquals(ParameterContainer::TYPE_INTEGER, $this->parameterContainer->offsetGetErrata('foo'));
    }

    #[TestDox('unit test: Test offsetGetErrata() return persisted errata data, if it exists')]
    public function testOffsetGetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        self::assertEquals(ParameterContainer::TYPE_INTEGER, $this->parameterContainer->offsetGetErrata('foo'));
    }

    #[TestDox('unit test: Test offsetHasErrata() will check if errata exists for a particular key')]
    public function testOffsetHasErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        self::assertTrue($this->parameterContainer->offsetHasErrata('foo'));
    }

    #[TestDox('unit test: Test offsetUnsetErrata() will unset data for a particular key')]
    public function testOffsetUnsetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        $this->parameterContainer->offsetUnsetErrata('foo');
        self::assertNull($this->parameterContainer->offsetGetErrata('foo'));
    }

    #[TestDox('unit test: Test getErrataIterator() will return an iterator for the errata data')]
    public function testGetErrataIterator(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        $data = $this->parameterContainer->getErrataIterator();
        self::assertInstanceOf('ArrayIterator', $data);
    }

    #[TestDox('unit test: Test getNamedArray()')]
    public function testGetNamedArray(): void
    {
        $data = $this->parameterContainer->getNamedArray();
        self::assertEquals(['foo' => 'bar'], $data);
    }

    #[TestDox('unit test: Test count() returns the proper count')]
    public function testCount(): void
    {
        self::assertEquals(1, $this->parameterContainer->count());
    }

    #[TestDox('unit test: Test current() returns the current element when used as an iterator')]
    public function testCurrent(): void
    {
        $value = $this->parameterContainer->current();
        self::assertEquals('bar', $value);
    }

    #[TestDox('unit test: Test next() increases the pointer when used as an iterator')]
    public function testNext(): void
    {
        $this->parameterContainer['bar'] = 'baz';
        $this->parameterContainer->next();
        self::assertEquals('baz', $this->parameterContainer->current());
    }

    #[TestDox("unit test: Test key() returns the name of the current item's name")]
    public function testKey(): void
    {
        self::assertEquals('foo', $this->parameterContainer->key());
    }

    #[TestDox('unit test: Test valid() returns whether the iterators current position is valid')]
    public function testValid(): void
    {
        self::assertTrue($this->parameterContainer->valid());
        $this->parameterContainer->next();
        self::assertFalse($this->parameterContainer->valid());
    }

    #[TestDox('unit test: Test rewind() resets the iterators pointer')]
    public function testRewind(): void
    {
        $this->parameterContainer->offsetSet('bar', 'baz');
        $this->parameterContainer->next();
        self::assertEquals('bar', $this->parameterContainer->key());
        $this->parameterContainer->rewind();
        self::assertEquals('foo', $this->parameterContainer->key());
    }
}
