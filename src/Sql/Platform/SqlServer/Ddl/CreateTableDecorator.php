<?php

namespace PhpDb\Sql\Platform\SqlServer\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use Override;

use function ltrim;

class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    /** @var CreateTable */
    public $subject;

    /**
     * @param CreateTable $subject
     * @return $this Provides a fluent interface
     */
    #[Override] public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return array
     */
    #[\Override]
    protected function processTable(?PlatformInterface $adapterPlatform = null)
    {
        $table = ($this->isTemporary ? '#' : '') . ltrim($this->table, '#');
        return [
            '',
            $adapterPlatform->quoteIdentifier($table),
        ];
    }
}
