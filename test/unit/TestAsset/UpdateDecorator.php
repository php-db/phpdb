<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class UpdateDecorator extends Sql\Update implements Sql\Platform\PlatformDecoratorInterface
{
    public object|null $subject;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(?object $subject): UpdateDecorator
    {
        $this->subject = $subject;
        return $this;
    }
}
