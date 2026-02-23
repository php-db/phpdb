<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\TableIdentifier;

class DropTable extends AbstractSql
{
    final public const TABLE = 'table';

    protected bool $ifExists = false;

    protected array $specifications = [
        self::TABLE => 'DROP TABLE %1$s%2$s',
    ];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        $this->table = $table;
    }

    public function ifExists(bool $ifExists = true): static
    {
        $this->ifExists = $ifExists;
        return $this;
    }

    public function getIfExists(): bool
    {
        return $this->ifExists;
    }

    /** @return string[] */
    protected function processTable(?PlatformInterface $adapterPlatform = null): array
    {
        return [
            $this->ifExists ? 'IF EXISTS ' : '',
            $this->resolveTable($this->table, $adapterPlatform),
        ];
    }
}
