<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

class AbstractPlatform implements PlatformDecoratorInterface, PreparableSqlInterface, SqlInterface
{
    protected ?object $subject = null;

    protected array $decorators = [];

    /**
     * {@inheritDoc}
     */
    public function setSubject($subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function setTypeDecorator(string $type, PlatformDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }

    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject
    ): PlatformDecoratorInterface|PreparableSqlInterface|SqlInterface {
        foreach ($this->decorators as $type => $decorator) {
            /** @phpstan-ignore-next-line instanceof with string class name is valid */
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
    public function getDecorators(): array
    {
        return $this->decorators;
    }

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\PreparableSqlInterface, thus calling '
                . 'prepareSqlString() has no effect'
            );
        }

        $decorator = $this->getTypeDecorator($this->subject);

        return $decorator instanceof PreparableSqlInterface
            ? $decorator->prepareSqlString($builder)
            : $this->subject->prepareSqlString($builder);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        if (! $this->subject instanceof SqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\SqlInterface, thus calling '
                . 'getSqlString() has no effect'
            );
        }

        return $this->getTypeDecorator($this->subject)->getSqlString($adapterPlatform);
    }
}
