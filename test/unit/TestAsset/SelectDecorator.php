<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class SelectDecorator extends Sql\Select implements Sql\Platform\PlatformDecoratorInterface
{
    protected ?object $subject;

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
