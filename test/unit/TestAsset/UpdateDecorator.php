<?php

namespace LaminasTest\Db\TestAsset;

use PhpDb\Sql;
use Override;

class UpdateDecorator extends Sql\Update implements Sql\Platform\PlatformDecoratorInterface
{
    protected $subject;

    /**
     * @param null|object $subject
     * @return $this Provides a fluent interface
     */
    #[Override] public function setSubject($subject): static
    {
        $this->subject = $subject;
        return $this;
    }
}
