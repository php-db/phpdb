<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

interface PlatformDecoratorInterface
{
    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(?object $subject): PlatformDecoratorInterface;
}
