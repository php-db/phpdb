<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Sql;

final class InsertDecorator extends Sql\Insert implements Sql\Platform\PlatformDecoratorInterface
{
    public object|null $subject;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(?object $subject): InsertDecorator
    {
        $this->subject = $subject;
        return $this;
    }
}
