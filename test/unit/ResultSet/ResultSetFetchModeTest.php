<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayObject;
use PDO;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

use function array_map;
use function iterator_to_array;

/**
 * The fetch mode is chosen by the caller, so a row reaching the result set need not be
 * an array. These cases drive a real SQLite result through the PDO driver and cover
 * every entry in Result::VALID_FETCH_MODES, so that a mode cannot start discarding rows
 * without a test noticing.
 */
#[CoversMethod(AbstractResultSet::class, 'rowToArray')]
#[CoversMethod(ResultSet::class, 'current')]
#[Group('unit')]
final class ResultSetFetchModeTest extends TestCase
{
    private PDO $pdo;

    /**
     * Every mode that yields rows, and the type each row arrives as.
     *
     * @return array<string, array{0: int, 1: class-string}>
     */
    public static function rowYieldingModeProvider(): array
    {
        return [
            'FETCH_ASSOC fills the ArrayObject prototype'    => [PDO::FETCH_ASSOC, ArrayObject::class],
            'FETCH_NUM fills the ArrayObject prototype'      => [PDO::FETCH_NUM, ArrayObject::class],
            'FETCH_BOTH fills the ArrayObject prototype'     => [PDO::FETCH_BOTH, ArrayObject::class],
            'FETCH_OBJ fills the ArrayObject prototype'      => [PDO::FETCH_OBJ, ArrayObject::class],
            'FETCH_NAMED fills the ArrayObject prototype'    => [PDO::FETCH_NAMED, ArrayObject::class],
            'FETCH_KEY_PAIR fills the ArrayObject prototype' => [PDO::FETCH_KEY_PAIR, ArrayObject::class],
            'FETCH_PROPS_LATE is a flag, so PDO falls back'  => [PDO::FETCH_PROPS_LATE, ArrayObject::class],
            'FETCH_CLASSTYPE is a flag, so PDO falls back'   => [PDO::FETCH_CLASSTYPE, ArrayObject::class],
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
     * FETCH_BOUND carries its row in variables bound by reference, so there is nothing
     * for the result set to hand back. Null is the correct answer here, not a loss.
     */
    #[Test]
    public function boundFetchModeYieldsNullAndPopulatesTheBoundColumns(): void
    {
        $result = new Result();
        $result->initialize($this->pdo->query('SELECT id, name FROM t'), null);
        $result->setFetchMode(PDO::FETCH_BOUND);

        $id       = null;
        $resource = $result->getResource();
        $resource->bindColumn(1, $id);

        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        $seen = [];
        foreach ($resultSet as $row) {
            static::assertNull($row);
            $seen[] = (int) $id;
        }

        static::assertSame([1, 2], $seen);
    }

    #[Test]
    #[DataProvider('rowYieldingModeProvider')]
    public function everyRowYieldingFetchModeReturnsBothRows(int $fetchMode): void
    {
        static::assertCount(2, $this->rowsFor($fetchMode));
    }

    #[Test]
    #[DataProvider('rowYieldingModeProvider')]
    public function everyRowYieldingFetchModeReturnsItsRows(int $fetchMode, string $expectedType): void
    {
        $rows = $this->rowsFor($fetchMode);

        static::assertContainsOnlyInstancesOf($expectedType, $rows);
    }

    #[Test]
    #[DataProvider('unusableModeProvider')]
    public function fetchModesNeedingExtraArgumentsCannotBeDriven(int $fetchMode): void
    {
        $this->expectException(Throwable::class);

        $this->rowsFor($fetchMode);
    }

    /**
     * FETCH_LAZY yields a PDORow, which resolves its columns through __get() and so
     * reduces to an empty array. There is no way to fill a prototype from it, and
     * emptying it silently is what this component used to do.
     */
    #[Test]
    public function lazyFetchModeIsRejectedRatherThanEmptied(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('exposes no properties');

        $this->rowsFor(PDO::FETCH_LAZY);
    }

    #[Test]
    public function objectRowsKeepTheirColumnValues(): void
    {
        $rows = $this->rowsFor(PDO::FETCH_OBJ);

        static::assertSame([1, 2], array_map(static fn(ArrayObject $row): int => (int) $row['id'], $rows));
    }

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE t (id INTEGER, name TEXT)');
        $this->pdo->exec('INSERT INTO t VALUES (1, "one"), (2, "two")');
    }

    /** @return list<mixed> */
    private function rowsFor(int $fetchMode): array
    {
        $result = new Result();
        $result->initialize($this->pdo->query('SELECT id, name FROM t'), null);
        $result->setFetchMode($fetchMode);

        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        return iterator_to_array($resultSet, preserve_keys: false);
    }
}
