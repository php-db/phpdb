<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

class AbstractPlatform implements PlatformDecoratorInterface, PreparableSqlInterface, SqlInterface
{
    /** @var object|null */
    protected $subject;

    /** @var PlatformDecoratorInterface[] */
    protected $decorators = [];

    /**
     * {@inheritDoc}
     */
    public function setSubject($subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string                     $type
     */
    public function setTypeDecorator($type, PlatformDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }

    /**
     * @param PreparableSqlInterface|SqlInterface $subject
     * @return PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface
     */
    public function getTypeDecorator($subject)
    {
        foreach ($this->decorators as $type => $decorator) {
            if ($subject instanceof $type) {
                $decorator->setSubject($subject);

                return $decorator;
            }
        }

        return $subject;
    }

    /**
     * @return array|PlatformDecoratorInterface[]
     */
    public function getDecorators()
    {
        return $this->decorators;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\PreparableSqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        $this->getTypeDecorator($this->subject)->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null)
    {
        if (! $this->subject instanceof SqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\SqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        return $this->getTypeDecorator($this->subject)->getSqlString($adapterPlatform);
    }
}
