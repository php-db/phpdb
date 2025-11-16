<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class DeleteDecorator extends Sql\Delete implements Sql\Platform\PlatformDecoratorInterface
{
    public object|null $subject;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(?object $subject): DeleteDecorator
    {
        $this->subject = $subject;
        return $this;
    }
}
