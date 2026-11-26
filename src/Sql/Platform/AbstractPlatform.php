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

/**
 * Renders Sql objects for a single dialect.
 *
 * A decorator is registered for each subject type whose rendering differs from standard
 * SQL. A subject with no registered decorator renders itself, so a platform holds only
 * the differences for the dialect it belongs to.
 */
class AbstractPlatform implements PlatformDecoratorInterface, PreparableSqlInterface, SqlInterface
{
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    /** @var array<class-string, PlatformDecoratorInterface> */
    protected array $decorators = [];

    /**
     * The decorator registered for this subject, bound to it, or null when it renders itself.
     */
    public function decoratorFor(PreparableSqlInterface|SqlInterface $subject): ?PlatformDecoratorInterface
    {
        foreach ($this->decorators as $type => $decorator) {
            /** @phpstan-ignore-next-line instanceof with a string class name is valid */
            if (! $subject instanceof $type) {
                continue;
            }

            $decorator->setSubject($subject);

            return $decorator;
        }

        return null;
    }

    /** @return array<class-string, PlatformDecoratorInterface> */
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
        $subject = $this->subject;
        if (! $subject instanceof SqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(SqlInterface::class, 'getSqlString()');
        }

        $renderer = $this->decoratorFor($subject);
        if (! $renderer instanceof SqlInterface) {
            $renderer = $subject;
        }

        return $renderer->getSqlString($adapterPlatform);
    }

    /**
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer,
    ): StatementContainerInterface {
        $subject = $this->subject;
        if (! $subject instanceof PreparableSqlInterface) {
            throw Exception\RuntimeException::forSubjectNotImplementing(
                PreparableSqlInterface::class,
                'prepareStatement()',
            );
        }

        $renderer = $this->decoratorFor($subject);
        if (! $renderer instanceof PreparableSqlInterface) {
            $renderer = $subject;
        }

        return $renderer->prepareStatement($adapter, $statementContainer);
    }

    /** {@inheritDoc} */
    #[Override]
    public function setSubject(SqlInterface|PreparableSqlInterface|null $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param class-string $type The Sql type this decorator renders.
     *
     * @throws Exception\RuntimeException When the decorator does not implement the type it is registered for.
     */
    public function setTypeDecorator(string $type, PlatformDecoratorInterface $decorator): void
    {
        if (! $decorator instanceof $type) {
            throw Exception\RuntimeException::forDecoratorWrongType($decorator::class, $type);
        }

        $this->decorators[$type] = $decorator;
    }
}
