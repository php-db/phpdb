<?php

namespace PhpDb\Sql\Platform\SqlServer\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function ltrim;

class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    /** @var CreateTable */
    protected $subject;

    /**
     * @param CreateTable $subject
     * @return $this Provides a fluent interface
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return array
     */
    protected function processTable(?PlatformInterface $adapterPlatform = null)
    {
        $table = ($this->isTemporary ? '#' : '') . ltrim($this->table, '#');
        return [
            '',
            $adapterPlatform->quoteIdentifier($table),
        ];
    }
}
