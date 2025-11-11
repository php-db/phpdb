<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class DeleteDecorator extends Sql\Delete implements Sql\Platform\PlatformDecoratorInterface
{
    /** @var object|null */
    public $subject;

    /**
     * @param null|object $subject
     * @return $this Provides a fluent interface
     */
    public function setSubject($subject): DeleteDecorator
    {
        $this->subject = $subject;
        return $this;
    }
}
