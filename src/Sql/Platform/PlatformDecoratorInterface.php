<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

interface PlatformDecoratorInterface
{
    /**
     * @param null|object $subject
     * @return $this
     */
    public function setSubject($subject);
}
