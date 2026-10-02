<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayObject;
use PDO;
use PDORow;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\UnexpectedValueException;
use PhpDb\ResultSet\ObjectResultSet;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

use function array_map;
use function iterator_to_array;
use function sprintf;

/**
 * The fetch mode is chosen by the caller, so a row reaching the result set need not be
 * an array. A result set fills its prototype from row data and refuses anything else
 * rather than reshaping an object the caller asked PDO for. These cases drive a real
 * SQLite result through the PDO driver and cover every entry in
 * Result::VALID_FETCH_MODES, so that a mode cannot change sides without a test noticing.
 */
#[CoversMethod(AbstractResultSet::class, 'getArrayData')]
#[CoversMethod(AbstractResultSet::class, 'unsupportedRowError')]
#[CoversMethod(ResultSet::class, 'current')]
#[CoversMethod(ResultSet::class, 'mapRow')]
#[CoversMethod(ObjectResultSet::class, 'mapRow')]
#[Group('unit')]
final class ResultSetFetchModeTest extends TestCase
{
    private PDO $pdo;

    /**
     * Modes whose rows are objects, which ObjectResultSet passes through untouched.
     *
     * @return array<string, array{0: int, 1: class-string}>
     */
    public static function objectYieldingModeProvider(): array
    {
        return [
            'FETCH_OBJ yields a stdClass' => [PDO::FETCH_OBJ, stdClass::class],
            'FETCH_LAZY yields a PDORow'  => [PDO::FETCH_LAZY, PDORow::class],
        ];
    }

    /**
     * Modes whose rows are not row data. The result set will not transform them, so each
     * names the type the caller is left holding.
     *
     * @return array<string, array{0: int, 1: string}>
     */
    public static function refusedModeProvider(): array
    {
        return [
            'FETCH_OBJ yields a stdClass'                    => [PDO::FETCH_OBJ, 'stdClass'],
            'FETCH_LAZY yields a PDORow'                     => [PDO::FETCH_LAZY, 'PDORow'],
            'FETCH_BOUND yields true and binds by reference' => [PDO::FETCH_BOUND, 'bool'],
        ];
    }

    /**
     * Every mode that yields row data, all of which fill the ArrayObject prototype.
     *
     * @return array<string, array{0: int}>
     */
    public static function rowYieldingModeProvider(): array
    {
        return [
            'FETCH_ASSOC'                                   => [PDO::FETCH_ASSOC],
            'FETCH_NUM'                                     => [PDO::FETCH_NUM],
            'FETCH_BOTH'                                    => [PDO::FETCH_BOTH],
            'FETCH_NAMED'                                   => [PDO::FETCH_NAMED],
            'FETCH_KEY_PAIR'                                => [PDO::FETCH_KEY_PAIR],
            'FETCH_PROPS_LATE is a flag, so PDO falls back' => [PDO::FETCH_PROPS_LATE],
            'FETCH_CLASSTYPE is a flag, so PDO falls back'  => [PDO::FETCH_CLASSTYPE],
        ];
    }

    /**
     * Modes Result::setFetchMode() accepts but cannot actually drive, because it takes
     * only an int and PDO needs more. Documented here so the gap stays visible.
     *
     * @return array<string, array{0: int}>
     */
    public static function unusableModeProvider(): array
    {
        return [
            'FETCH_CLASS needs a class name PDO is never given'   => [PDO::FETCH_CLASS],
            'FETCH_INTO needs a target object PDO is never given' => [PDO::FETCH_INTO],
            'FETCH_FUNC is rejected by PDO outside fetchAll()'    => [PDO::FETCH_FUNC],
        ];
    }

    /**
     * FETCH_BOUND carries its row in variables bound by reference and yields only a
     * success flag, so the columns still arrive even though the row itself is refused.
     */
    #[Test]
    public function boundFetchModeStillPopulatesTheBoundColumns(): void
    {
        $result = new Result();
        $result->initialize($this->pdo->query('SELECT id, name FROM t'), null);
        $result->setFetchMode(PDO::FETCH_BOUND);

        $id       = null;
        $resource = $result->getResource();
        $resource->bindColumn(1, $id);

        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        self::expectException(UnexpectedValueException::class);

        try {
            iterator_to_array($resultSet, preserve_keys: false);
        } finally {
            static::assertSame(1, (int) $id);
        }
    }

    #[Test]
    #[DataProvider('rowYieldingModeProvider')]
    public function everyRowYieldingFetchModeFillsThePrototype(int $fetchMode): void
    {
        static::assertContainsOnlyInstancesOf(ArrayObject::class, $this->rowsFor($fetchMode));
    }

    #[Test]
    #[DataProvider('rowYieldingModeProvider')]
    public function everyRowYieldingFetchModeReturnsBothRows(int $fetchMode): void
    {
        static::assertCount(2, $this->rowsFor($fetchMode));
    }

    #[Test]
    #[DataProvider('unusableModeProvider')]
    public function fetchModesNeedingExtraArgumentsCannotBeDriven(int $fetchMode): void
    {
        $this->expectException(Throwable::class);

        $this->rowsFor($fetchMode);
    }

    #[Test]
    #[DataProvider('refusedModeProvider')]
    public function fetchModesYieldingSomethingOtherThanRowDataAreRefused(
        int $fetchMode,
        string $rowType,
    ): void {
        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(sprintf('A row of type "%s"', $rowType));

        $this->rowsFor($fetchMode);
    }

    #[Test]
    public function objectResultSetKeepsTheColumnValuesOfAnObjectRow(): void
    {
        $rows = $this->rowsFor(PDO::FETCH_OBJ, new ObjectResultSet());

        static::assertSame([1, 2], array_map(static fn(object $row): int => (int) $row->id, $rows));
    }

    /**
     * The modes ResultSet refuses are the ones ObjectResultSet exists for; it yields the
     * caller's own objects rather than reshaping them.
     */
    #[Test]
    #[DataProvider('objectYieldingModeProvider')]
    public function objectYieldingFetchModesAreServedByObjectResultSet(
        int $fetchMode,
        string $rowType,
    ): void {
        $rows = $this->rowsFor($fetchMode, new ObjectResultSet());

        static::assertCount(2, $rows);
        static::assertContainsOnlyInstancesOf($rowType, $rows);
    }

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE t (id INTEGER, name TEXT)');
        $this->pdo->exec('INSERT INTO t VALUES (1, "one"), (2, "two")');
    }

    /** @return list<mixed> */
    private function rowsFor(int $fetchMode, ?ResultSetInterface $resultSet = null): array
    {
        $result = new Result();
        $result->initialize($this->pdo->query('SELECT id, name FROM t'), null);
        $result->setFetchMode($fetchMode);

        $resultSet ??= new ResultSet();
        $resultSet->initialize($result);

        return iterator_to_array($resultSet, preserve_keys: false);
    }
}
