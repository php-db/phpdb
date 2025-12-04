<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

interface PlatformDecoratorInterface
{
    public function setSubject(?object $subject): PlatformDecoratorInterface;
}
