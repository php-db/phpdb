<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

class AbstractPlatform implements PlatformDecoratorInterface, PreparableSqlInterface, SqlInterface
{
    protected SqlInterface|PreparableSqlInterface $subject;

    protected array $decorators = [];

    /**
     * @return array|PlatformDecoratorInterface[]
     */
    public function getDecorators(): array
    {
        return $this->decorators;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        if (! $this->subject instanceof SqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(SqlInterface::class, 'getSqlString()');
        }

        return $this->getTypeDecorator($this->subject)->getSqlString($adapterPlatform);
    }

    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject,
    ): PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface {
        foreach ($this->decorators as $type => $decorator) {
            /** @phpstan-ignore-next-line instanceof with string class name is valid */
            if (! $subject instanceof $type) {
                continue;
            }

            $decorator->setSubject($subject);
            return $decorator;
        }

        return $subject;
    }

    /**
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer,
    ): StatementContainerInterface {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(
                PreparableSqlInterface::class,
                'prepareStatement()',
            );
        }

        $this->getTypeDecorator($this->subject)->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setSubject($subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function setTypeDecorator(string $type, PlatformDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }
}
