<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Sql;

class SelectDecorator extends Sql\Select implements Sql\Platform\PlatformDecoratorInterface
{
    /** @var null|object */
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
