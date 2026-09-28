<?php

declare(strict_types=1);

namespace PhpDbTest\Exception;

use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use OverflowException;
use PhpDb\Exception\ExceptionInterface;
use PhpDbTest\Exception\TestAsset\ExceptionClassLocator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RangeException;
use ReflectionClass;
use RuntimeException;
use Throwable;
use UnderflowException;
use UnexpectedValueException;

use function is_subclass_of;
use function ksort;

/**
 * Structural guards over the exception namespaces.
 *
 * A class named after an SPL exception extends that SPL exception, every concrete
 * exception is reachable through the package marker interface, and every named
 * constructor has the template constant its name implies.
 */
#[Group('unit')]
#[CoversNothing]
final class ExceptionHierarchyTest extends TestCase
{
    /**
     * Short class name to the SPL class it claims by name.
     *
     * ErrorException is absent deliberately. Both PhpDb\Exception\ErrorException and
     * PhpDb\Adapter\Exception\ErrorException extend \Exception rather than
     * \ErrorException, and \ErrorException's third constructor argument is $severity
     * where \Exception's is $previous, so correcting the parent would change a released
     * constructor contract.
     *
     * @var array<string, class-string<Throwable>>
     */
    private const array SPL_PARENTS = [
        'DomainException'          => DomainException::class,
        'InvalidArgumentException' => InvalidArgumentException::class,
        'LengthException'          => LengthException::class,
        'LogicException'           => LogicException::class,
        'OutOfBoundsException'     => OutOfBoundsException::class,
        'OutOfRangeException'      => OutOfRangeException::class,
        'OverflowException'        => OverflowException::class,
        'RangeException'           => RangeException::class,
        'RuntimeException'         => RuntimeException::class,
        'UnderflowException'       => UnderflowException::class,
        'UnexpectedValueException' => UnexpectedValueException::class,
    ];

    /**
     * PhpDb\Adapter\Exception\VunerablePlatformQuoteException is exempt from the naming
     * guard: its factory is named for its arguments rather than the fault, and holds its
     * message inline. PR 5 of the exception RFC removes the class, resolving both.
     */
    private const string UNCONVERTED_CLASS = 'VunerablePlatformQuoteException';

    /** @return array<string, array{string}> */
    public static function exceptionClassProvider(): array
    {
        $cases = [];
        foreach (ExceptionClassLocator::concreteClasses() as $class) {
            $cases[$class] = [$class];
        }

        ksort($cases);

        return $cases;
    }

    /** @return array<string, array{string, string, string}> */
    public static function namedConstructorProvider(): array
    {
        $cases = [];
        foreach (ExceptionClassLocator::concreteClasses() as $shortName => $class) {
            if (self::UNCONVERTED_CLASS === $shortName) {
                continue;
            }

            foreach (ExceptionClassLocator::namedConstructors($class) as $method) {
                $cases["{$class}::{$method}()"] = [
                    $class,
                    $method,
                    ExceptionClassLocator::templateConstantFor($method),
                ];
            }
        }

        ksort($cases);

        return $cases;
    }

    /** @return array<string, array{string, string}> */
    public static function splShapedExceptionProvider(): array
    {
        $cases = [];
        foreach (ExceptionClassLocator::concreteClasses() as $shortName => $class) {
            $splParent = self::SPL_PARENTS[$shortName] ?? null;
            if (null === $splParent) {
                continue;
            }

            $cases[$class] = [$class, $splParent];
        }

        ksort($cases);

        return $cases;
    }

    #[Test]
    #[DataProvider('exceptionClassProvider')]
    public function exceptionIsReachableThroughThePackageMarkerInterface(string $class): void
    {
        self::assertTrue(
            is_subclass_of($class, ExceptionInterface::class),
            "{$class} does not implement " . ExceptionInterface::class,
        );
    }

    #[Test]
    #[DataProvider('splShapedExceptionProvider')]
    public function exceptionNamedAfterAnSplClassExtendsThatSplClass(string $class, string $splParent): void
    {
        self::assertTrue(
            is_subclass_of($class, $splParent),
            "{$class} is named after {$splParent} but does not extend it",
        );
    }

    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorHasATemplateConstantMirroringItsName(
        string $class,
        string $method,
        string $expectedConstant,
    ): void {
        self::assertArrayHasKey(
            $expectedConstant,
            (new ReflectionClass($class))->getConstants(),
            "{$class}::{$method}() has no {$expectedConstant} template constant",
        );
    }
}
