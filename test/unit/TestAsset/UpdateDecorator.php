<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Sql;

class UpdateDecorator extends Sql\Update implements Sql\Platform\PlatformDecoratorInterface
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
