<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Sql;
use Override;

class SelectDecorator extends Sql\Select implements Sql\Platform\PlatformDecoratorInterface
{
    public $subject;

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
