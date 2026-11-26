# Platforms and Decorators

Rendering a statement for a driver is split across two objects, and both are
called a platform:

| | Contract | Responsibility | Supplied by |
|---|---|---|---|
| Adapter platform | `PhpDb\Adapter\Platform\PlatformInterface` | quoting identifiers and values | the driver package (`Mysql\AdapterPlatform`, or `Sqlite\AdapterPlatform`), or `Adapter\Platform\Sql92` for standard SQL |
| SQL platform | `PhpDb\Sql\Platform\PlatformDecoratorInterface` | rendering `Sql` objects, decorating the statements a dialect writes differently | the driver package, or `Sql\Platform\Platform` for standard SQL |

The adapter platform only knows syntax. The SQL platform knows the language: it
takes a `Select`, `Insert`, `CreateTable` and so on and turns it into a string.

## One platform per adapter

`Sql` takes its SQL platform from the adapter it was constructed with:

```php
$sql = new Sql($adapter);              // $adapter->getPlatform()->getSqlPlatformDecorator()
$select = $sql->select('users')->where(['active' => 1]);
```

Nothing is resolved while rendering. The dialect is fixed when the adapter is
built, so a statement is always rendered for the platform it will run on. Two
drivers in one process mean two adapters and two `Sql` instances, each with its
own platform, which is what a cross-platform migration needs.

## Decorators

A platform registers a decorator for each subject type whose rendering differs
from standard SQL:

```php
final class Platform extends AbstractPlatform
{
    public function __construct()
    {
        $this->setTypeDecorator(Select::class, new SelectDecorator());
        $this->setTypeDecorator(CreateTable::class, new CreateTableDecorator());
    }
}
```

A decorator must implement the type it is registered for. `setTypeDecorator()`
rejects anything else with `Sql\Exception\RuntimeException::DECORATOR_WRONG_TYPE`,
because a decorator that cannot render the subject it is registered for would
otherwise fail later, at the point of use.

Lookup goes through `decoratorFor()`:

```php
$decorator = $platform->decoratorFor($subject);   // bound to $subject, or null

return ($decorator ?? $subject)->getSqlString();
```

A `null` result means this dialect writes the statement as standard SQL, so the
subject renders itself. That is the common case: a platform holds only the
differences for its dialect, and standard SQL needs none.

## Writing a driver package

A driver package is complete when it provides all of the following and needs no
change to `phpdb`:

- a `ConfigProvider` registering the driver, connection, statement, result,
  platform and metadata services;
- a `Driver` implementation;
- a `Connection` implementation;
- an `AdapterPlatform` for naming, identifier quoting and value quoting;
- optionally, a `Sql\Platform` carrying the decorators for its dialect;
- optionally, driver features for capabilities that SQL cannot express, chosen by
  the driver rather than named by the abstraction.

Keep dialect knowledge in the package that owns the dialect. Core must not name a
driver class, and `PlatformInterface::getName()` is for labels only, never for
dispatch.