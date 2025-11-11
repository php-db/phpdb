<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class InsertDecorator extends Sql\Insert implements Sql\Platform\PlatformDecoratorInterface
{
    /** @var object|null */
    public $subject;

    /**
     * @param null|object $subject
     * @return $this Provides a fluent interface
     */
    public function setSubject($subject): static
    {
        $this->subject = $subject;
        return $this;
    }
}
