<?php

namespace LaminasTest\Db\TestAsset;

use PhpDb\Sql;
use Override;

class InsertDecorator extends Sql\Insert implements Sql\Platform\PlatformDecoratorInterface
{
    public $subject;

    /**
     * @param null|object $subject
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setSubject($subject): static
    {
        $this->subject = $subject;
        return $this;
    }
}
