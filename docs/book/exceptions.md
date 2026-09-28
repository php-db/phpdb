# Exceptions

Every exception this package throws is built by a `static` named constructor on a
component-owned exception class. The message template sits beside it as a
`final public const string`. No exception is constructed with a message assembled at
the throw site.

## The convention

```php
namespace PhpDb\TableGateway\Exception;

use PhpDb\Exception;

use function sprintf;

class RuntimeException extends Exception\RuntimeException implements ExceptionInterface
{
    final public const string NO_PRIMARY_KEY = 'No information was provided to the RowGatewayFeature'
        . ' and/or no MetadataFeature could be consulted to find the primary key'
        . ' necessary for RowGateway object creation.';

    final public const string INVALID_MAGIC_CALL = 'Invalid method (%s) called, caught by %s::__call()';

    public static function forNoPrimaryKey(): self
    {
        return new self(self::NO_PRIMARY_KEY);
    }

    public static function forInvalidMagicCall(string $method, string $class): self
    {
        return new self(sprintf(self::INVALID_MAGIC_CALL, $method, $class));
    }
}
```

- Named constructors are `static`, return `self` and start with `for`.
- Each one has a single `final public const string` template beside it.
- The constant name mirrors the method name: `forNoPrimaryKey()` uses `NO_PRIMARY_KEY`,
  `forInvalidMagicCall()` uses `INVALID_MAGIC_CALL`. Strip the `for` prefix and
  upper-snake the rest.
- Use `new self(self::CONST)` when the template has no placeholder and
  `new self(sprintf(self::CONST, ...))` when it does. `sprintf()` on a template with no
  `%` placeholder returns its own argument, so it is not written.
- Quote a `%s` that holds a caller-supplied identifier, such as a column or parameter
  name. Leave a class name, method name or count unquoted.

The constants are `final` because the component base classes are not. Adapter packages
extend `PhpDb\Adapter\Exception\RuntimeException`, so without `final` a subclass could
redefine a published template.

## When a condition earns its own exception class

Naming a condition in a class instead of a method is a departure from the convention,
not an application of it. A condition earns its own class only when it carries something
a named constructor cannot:

1. **Extra state, with accessors.** `PhpDb\Adapter\Exception\InvalidConnectionParametersException`
   holds the connection parameters it rejected.
2. **An external interface contract.** `PhpDb\Exception\ContainerException` implements
   PSR-11's `ContainerExceptionInterface`.
3. **A condition a consumer can act on.** `PhpDb\Adapter\Exception\InvalidQueryException`
   is thrown by every adapter and caught to log the failing SQL or retry.

Everything else is a named constructor on the component's SPL-shaped class.

## Component namespaces

Each component owns an `Exception` namespace holding an `ExceptionInterface` that extends
`PhpDb\Exception\ExceptionInterface`, plus the SPL-shaped classes it needs. A class named
after an SPL exception extends that SPL exception, so `catch (\RuntimeException)` and
`catch (PhpDb\Exception\ExceptionInterface)` both behave as a reader expects.
`PhpDbTest\Exception\ExceptionHierarchyTest` enforces both rules across `src/`.
