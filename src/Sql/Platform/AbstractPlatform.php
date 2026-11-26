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
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    /**
     * Decorators keyed by the subject type they decorate; Platform nests them under a platform name first
     *
     * @var array<string, PlatformDecoratorInterface|array<string, PlatformDecoratorInterface>>
     */
    protected array $decorators = [];

    /**
     * @return array<string, PlatformDecoratorInterface>
     */
    public function getDecorators(): array
    {
        /** @var array<string, PlatformDecoratorInterface> */
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

        return $this->requireDecoratorImplementing(
            $this->getTypeDecorator($this->subject),
            SqlInterface::class,
            'getSqlString()',
        )->getSqlString($adapterPlatform);
    }

    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject,
    ): PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface {
        /** @var array<string, PlatformDecoratorInterface> $decorators */
        $decorators = $this->decorators;
        foreach ($decorators as $type => $decorator) {
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

        $this->requireDecoratorImplementing(
            $this->getTypeDecorator($this->subject),
            PreparableSqlInterface::class,
            'prepareStatement()',
        )->prepareStatement($adapter, $statementContainer);

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

    /**
     * Narrows the resolved decorator to the interface whose method is about to be delegated to it
     *
     * @template T of PreparableSqlInterface|SqlInterface
     * @param class-string<T> $interface
     * @return T
     * @throws Exception\RuntimeException
     */
    final protected function requireDecoratorImplementing(
        PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface $decorator,
        string $interface,
        string $method,
    ): PreparableSqlInterface|SqlInterface {
        if (! $decorator instanceof $interface) {
            throw Exception\RuntimeException::forDecoratorNotImplementing($decorator::class, $interface, $method);
        }

        return $decorator;
    }
}
