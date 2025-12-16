<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractPreparableSql;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\TableIdentifier;

class DropTable extends AbstractPreparableSql implements SqlInterface
{
    final public const TABLE = 'table';

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        $this->table = $table;
    }

    /** @inheritDoc */
    #[Override]
    protected function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        $builder = new PreparableSqlBuilder($platform, $driver, $parameterContainer);
        $q       = $builder->q;

        $sql = 'DROP TABLE ';
        if ($this->table instanceof TableIdentifier) {
            $schema = $this->table->getSchema();
            $sql   .= $schema !== null ? "{$q}{$schema}{$q}.{$q}{$this->table->getTable()}{$q}"
                : "{$q}{$this->table->getTable()}{$q}";
        } else {
            $sql .= "{$q}{$this->table}{$q}";
        }

        return $sql;
    }
}
