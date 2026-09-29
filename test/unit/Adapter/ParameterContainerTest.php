<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use Override;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\ParameterContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
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
#[CoversMethod(ParameterContainer::class, '__construct')]
#[CoversMethod(ParameterContainer::class, 'offsetSetReference')]
#[CoversMethod(ParameterContainer::class, 'getPositionalArray')]
#[Group('unit')]
final class ParameterContainerTest extends TestCase
{
    protected ParameterContainer $parameterContainer;

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithDataPopulatesContainer(): void
    {
        $container = new ParameterContainer(['a' => 1, 'b' => 2]);

        static::assertSame(2, $container->count());
        static::assertSame(1, $container->offsetGet('a'));
        static::assertSame(2, $container->offsetGet('b'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test count() returns the proper count')]
    public function countsStoredParameters(): void
    {
        static::assertSame(1, $this->parameterContainer->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test current() returns the current element when used as an iterator')]
    public function current(): void
    {
        $value = $this->parameterContainer->current();
        static::assertSame('bar', $value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test getErrataIterator() will return an iterator for the errata data')]
    public function getErrataIterator(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        $data = $this->parameterContainer->getErrataIterator();
        static::assertInstanceOf('ArrayIterator', $data);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test getMaxLengthIterator() will return an iterator for the errata data')]
    public function getMaxLengthIterator(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        $data = $this->parameterContainer->getMaxLengthIterator();
        static::assertInstanceOf('ArrayIterator', $data);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test getNamedArray()')]
    public function getNamedArray(): void
    {
        $data = $this->parameterContainer->getNamedArray();
        static::assertEquals(['foo' => 'bar'], $data);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPositionalArrayReturnsValues(): void
    {
        $container = new ParameterContainer(['a' => 1, 'b' => 2, 'c' => 3]);

        static::assertSame([1, 2, 3], $container->getPositionalArray());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox("unit test: Test key() returns the name of the current item's name")]
    public function key(): void
    {
        static::assertSame('foo', $this->parameterContainer->key());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test next() increases the pointer when used as an iterator')]
    public function next(): void
    {
        $this->parameterContainer['bar'] = 'baz';
        $this->parameterContainer->next();
        static::assertSame('baz', $this->parameterContainer->current());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetExists() returns proper values via method call and isset()')]
    public function offsetExists(): void
    {
        static::assertTrue($this->parameterContainer->offsetExists('foo'));
        static::assertTrue(isset($this->parameterContainer['foo']));
        static::assertFalse($this->parameterContainer->offsetExists('bar'));
        static::assertFalse(isset($this->parameterContainer['bar']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetGet() returns proper values via method call and array access')]
    public function offsetGet(): void
    {
        static::assertSame('bar', $this->parameterContainer->offsetGet('foo'));
        static::assertSame('bar', $this->parameterContainer['foo']);

        static::assertNull($this->parameterContainer->offsetGet('bar'));

        // @todo determine what should come back here
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetGetErrata() return persisted errata data, if it exists')]
    public function offsetGetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        static::assertEquals(ParameterContainer::TYPE_INTEGER, $this->parameterContainer->offsetGetErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetGetErrataByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);

        static::assertSame(ParameterContainer::TYPE_INTEGER, $container->offsetGetErrata(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetGetErrataThrowsWhenNameDoesNotExist(): void
    {
        $container = new ParameterContainer();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_DATA);

        $container->offsetGetErrata('nonexistent');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetGetMaxLengthByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetMaxLength('foo', 50);

        static::assertSame(50, $container->offsetGetMaxLength(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetGetMaxLengthThrowsWhenNameDoesNotExist(): void
    {
        $container = new ParameterContainer();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_DATA);

        $container->offsetGetMaxLength('nonexistent');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetHasErrata() will check if errata exists for a particular key')]
    public function offsetHasErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        static::assertTrue($this->parameterContainer->offsetHasErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetHasErrataByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetErrata('foo', ParameterContainer::TYPE_STRING);

        static::assertTrue($container->offsetHasErrata(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetHasMaxLength() will check if errata exists for a particular key')]
    public function offsetHasMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        static::assertTrue($this->parameterContainer->offsetHasMaxLength('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetHasMaxLengthByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetMaxLength('foo', 50);

        static::assertTrue($container->offsetHasMaxLength(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetSet() works via method call and array access')]
    public function offsetSet(): void
    {
        $this->parameterContainer->offsetSet('boo', 'baz');
        static::assertSame('baz', $this->parameterContainer->offsetGet('boo'));

        $this->parameterContainer->offsetSet('1', 'book', ParameterContainer::TYPE_STRING, 4);
        static::assertEquals(
            ['foo' => 'bar', 'boo' => 'baz', '1' => 'book'],
            $this->parameterContainer->getNamedArray(),
        );

        static::assertSame('string', $this->parameterContainer->offsetGetErrata('1'));
        static::assertSame(4, $this->parameterContainer->offsetGetMaxLength('1'));

        // test that setting an index applies to correct named parameter
        $this->parameterContainer[0] = 'Zero';
        $this->parameterContainer[1] = 'One';
        static::assertEquals(
            ['foo' => 'Zero', 'boo' => 'One', '1' => 'book'],
            $this->parameterContainer->getNamedArray(),
        );
        static::assertEquals(
            [0 => 'Zero', 1 => 'One', 2 => 'book'],
            $this->parameterContainer->getPositionalArray(),
        );

        // test no-index applies
        $this->parameterContainer['buffer'] = 'A buffer Element';
        $this->parameterContainer[]         = 'Second To Last';
        $this->parameterContainer[]         = 'Last';
        static::assertEquals(
            [
                'foo'    => 'Zero',
                'boo'    => 'One',
                '1'      => 'book',
                'buffer' => 'A buffer Element',
                '4'      => 'Second To Last',
                '5'      => 'Last',
            ],
            $this->parameterContainer->getNamedArray(),
        );
        static::assertEquals(
            [0 => 'Zero', 1 => 'One', 2 => 'book', 3 => 'A buffer Element', 4 => 'Second To Last', 5 => 'Last'],
            $this->parameterContainer->getPositionalArray(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('
        unit test: Test offsetSetMaxLength() will persist errata data
        unit test: Test offsetGetMaxLength() return persisted errata data, if it exists
    ')]
    public function offsetSetAndGetMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        static::assertSame(100, $this->parameterContainer->offsetGetMaxLength('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetSetErrata() will persist errata data')]
    public function offsetSetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        static::assertEquals(ParameterContainer::TYPE_INTEGER, $this->parameterContainer->offsetGetErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetErrataByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetErrata(0, ParameterContainer::TYPE_STRING);

        static::assertSame(ParameterContainer::TYPE_STRING, $container->offsetGetErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetMaxLengthByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetMaxLength(0, 50);

        static::assertSame(50, $container->offsetGetMaxLength('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetReferenceCreatesReference(): void
    {
        $container = new ParameterContainer(['source' => 'original']);
        $container->offsetSetReference('alias', 'source');

        static::assertSame('original', $container->offsetGet('alias'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetThrowsOnInvalidKeyType(): void
    {
        $container = new ParameterContainer();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::INVALID_KEY_TYPE);

        $container->offsetSet(1.5, 'value');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetWithIntNotInPositionsCastsToString(): void
    {
        $container = new ParameterContainer();
        $container->offsetSet(5, 'value');

        static::assertSame('value', $container->offsetGet('5'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetWithNameMappingMatchForNonColonName(): void
    {
        $container = new ParameterContainer();
        $container->offsetSet('c_0', ':myparam');
        $container->offsetSet('myparam', 'updated');

        static::assertSame('updated', $container->offsetGet('c_0'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetUnset() works via method call and array access')]
    public function offsetUnset(): void
    {
        $this->parameterContainer->offsetSet('boo', 'baz');
        static::assertTrue($this->parameterContainer->offsetExists('boo'));

        $this->parameterContainer->offsetUnset('boo');
        static::assertFalse($this->parameterContainer->offsetExists('boo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetUnsetByPositionalIndex(): void
    {
        $container = new ParameterContainer(['a' => 'one', 'b' => 'two']);
        $container->offsetUnset(0);

        static::assertFalse($container->offsetExists('a'));
        static::assertTrue($container->offsetExists('b'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetUnsetErrata() will unset data for a particular key')]
    public function offsetUnsetErrata(): void
    {
        $this->parameterContainer->offsetSetErrata('foo', ParameterContainer::TYPE_INTEGER);
        $this->parameterContainer->offsetUnsetErrata('foo');
        static::assertNull($this->parameterContainer->offsetGetErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetUnsetErrataByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetErrata('foo', ParameterContainer::TYPE_STRING);
        $container->offsetUnsetErrata(0);

        static::assertNull($container->offsetGetErrata('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetUnsetErrataThrowsWhenNameDoesNotExist(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_DATA);

        $container->offsetUnsetErrata('foo');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test offsetUnsetMaxLength() will unset data for a particular key')]
    public function offsetUnsetMaxLength(): void
    {
        $this->parameterContainer->offsetSetMaxLength('foo', 100);
        $this->parameterContainer->offsetUnsetMaxLength('foo');
        static::assertNull($this->parameterContainer->offsetGetMaxLength('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetUnsetMaxLengthByPositionalIndex(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);
        $container->offsetSetMaxLength('foo', 50);
        $container->offsetUnsetMaxLength(0);

        static::assertNull($container->offsetGetMaxLength('foo'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetUnsetMaxLengthThrowsWhenNameDoesNotExist(): void
    {
        $container = new ParameterContainer(['foo' => 'bar']);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_DATA);

        $container->offsetUnsetMaxLength('foo');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test rewind() resets the iterators pointer')]
    public function rewind(): void
    {
        $this->parameterContainer->offsetSet('bar', 'baz');
        $this->parameterContainer->next();
        static::assertSame('bar', $this->parameterContainer->key());
        $this->parameterContainer->rewind();
        static::assertSame('foo', $this->parameterContainer->key());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test setFromArray() will populate the container')]
    public function setFromArray(): void
    {
        $this->parameterContainer->setFromArray(['bar' => 'baz']);
        static::assertSame('baz', $this->parameterContainer['bar']);
    }

    /**
     * Handle statement parameters - https://github.com/laminas/laminas-db/issues/47
     *
     * @see Insert::procesInsert as example
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function setFromArrayNamed(): void
    {
        $this->parameterContainer->offsetSet('c_0', ':myparam');
        $this->parameterContainer->setFromArray([':myparam' => 'baz']);
        static::assertSame('baz', $this->parameterContainer['c_0']);
        static::assertSame('baz', $this->parameterContainer[':myparam']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[TestDox('unit test: Test valid() returns whether the iterators current position is valid')]
    public function valid(): void
    {
        static::assertTrue($this->parameterContainer->valid());
        $this->parameterContainer->next();
        static::assertFalse($this->parameterContainer->valid());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->parameterContainer = new ParameterContainer(['foo' => 'bar']);
    }
}
