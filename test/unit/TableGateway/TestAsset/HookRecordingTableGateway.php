<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\TestAsset;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\AbstractTableGateway;

/**
 * Overrides each protected execute hook, as a gateway subclass may, and records which ran.
 */
final class HookRecordingTableGateway extends AbstractTableGateway
{
    /** @var list<string> */
    public array $executed = [];

    public function __construct(string $table, AdapterInterface $adapter)
    {
        $this->table   = $table;
        $this->adapter = $adapter;
    }

    #[Override]
    protected function executeDelete(Delete $delete): int
    {
        $this->executed[] = 'delete';

        return parent::executeDelete($delete);
    }

    #[Override]
    protected function executeInsert(Insert $insert): int
    {
        $this->executed[] = 'insert';

        return parent::executeInsert($insert);
    }

    #[Override]
    protected function executeSelect(Select $select): ResultSetInterface
    {
        $this->executed[] = 'select';

        return parent::executeSelect($select);
    }

    #[Override]
    protected function executeUpdate(Update $update): int
    {
        $this->executed[] = 'update';

        return parent::executeUpdate($update);
    }
}
