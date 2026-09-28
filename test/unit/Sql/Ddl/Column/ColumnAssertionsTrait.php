<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\CreateTable;

trait ColumnAssertionsTrait
{
    /**
     * @return array<string, array{int|null}>
     */
    public static function missingLengthProvider(): array
    {
        return [
            'null length' => [null],
            'zero length' => [0],
        ];
    }

    /**
     * Asserts what a column renders to inside CREATE TABLE on the default SQL-92 platform.
     */
    private static function assertColumnRenders(string $expected, ColumnInterface $column): void
    {
        $createTable = new CreateTable('t');
        $createTable->addColumn($column);

        self::assertSame("CREATE TABLE \"t\" ( \n    {$expected} \n)", $createTable->getSqlString());
    }
}
