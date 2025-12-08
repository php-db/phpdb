# Profiler

The profiler component allows you to collect timing information about database
queries executed through phpdb. This is invaluable during development for
identifying slow queries, debugging SQL issues, and integrating with
development tools and logging systems.

## Basic Usage

The `Profiler` class implements `ProfilerInterface` and can be attached to any adapter:

```php
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Profiler\Profiler;

// Create a profiler instance
$profiler = new Profiler();

// Attach to an existing adapter
$adapter->setProfiler($profiler);

// Or pass it during adapter construction
$adapter = new Adapter($driver, $platform, $resultSetPrototype, $profiler);
```

Once attached, the profiler automatically tracks all queries executed through
the adapter.

## Retrieving Profile Data

After executing queries, you can retrieve profiling information:

```php title="Get the Last Profile"
$adapter->query('SELECT * FROM users WHERE status = ?', ['active']);

$lastProfile = $profiler->getLastProfile();

// Returns:
// [
//     'sql'        => 'SELECT * FROM users WHERE status = ?',
//     'parameters' => ParameterContainer instance,
//     'start'      => 1702054800.123456,  // microtime(true) when query started
//     'end'        => 1702054800.234567,  // microtime(true) when query finished
//     'elapse'     => 0.111111,           // execution time in seconds
// ]
```

```php title="Get All Profiles"
// Execute several queries
$adapter->query('SELECT * FROM users');
$adapter->query('SELECT * FROM orders WHERE user_id = ?', [42]);
$adapter->query('UPDATE users SET last_login = NOW() WHERE id = ?', [42]);

// Get all collected profiles
$allProfiles = $profiler->getProfiles();

foreach ($allProfiles as $index => $profile) {
    echo sprintf(
        "Query %d: %s (%.4f seconds)\n",
        $index + 1,
        $profile['sql'],
        $profile['elapse']
    );
}
```

## Profile Data Structure

Each profile entry contains:

| Key          | Type                       | Description                    |
| ------------ | -------------------------- | ------------------------------ |
| `sql`        | `string`                   | The executed SQL query         |
| `parameters` | `ParameterContainer\|null` | Bound parameters (if any)      |
| `start`      | `float`                    | Query start (Unix timestamp)   |
| `end`        | `float`                    | Query end (Unix timestamp)     |
| `elapse`     | `float`                    | Execution time in seconds      |

## Integration with Development Tools

### Logging Slow Queries

Create a simple slow query logger:

```php
use PhpDb\Adapter\Profiler\Profiler;
use Psr\Log\LoggerInterface;

class SlowQueryLogger
{
    public function __construct(
        private Profiler $profiler,
        private LoggerInterface $logger,
        private float $threshold = 1.0 // Log queries taking more than 1 second
    ) {
    }

    public function checkLastQuery(): void
    {
        $profile = $this->profiler->getLastProfile();

        if ($profile && $profile['elapse'] > $this->threshold) {
            $this->logger->warning('Slow query detected', [
                'sql' => $profile['sql'],
                'time' => $profile['elapse'],
                'parameters' => $profile['parameters']?->getNamedArray(),
            ]);
        }
    }

    public function getSlowQueries(): array
    {
        return array_filter(
            $this->profiler->getProfiles(),
            fn($profile) => $profile['elapse'] > $this->threshold
        );
    }
}
```

### Debug Toolbar Integration

Integrate with debug toolbars by collecting query information:

```php
class DebugBarCollector
{
    public function __construct(
        private Profiler $profiler
    ) {
    }

    public function collect(): array
    {
        $profiles = $this->profiler->getProfiles();
        $totalTime = 0;
        $queries = [];

        foreach ($profiles as $profile) {
            $totalTime += $profile['elapse'];
            $queries[] = [
                'sql' => $profile['sql'],
                'params' => $profile['parameters']?->getNamedArray() ?? [],
                'duration' => round($profile['elapse'] * 1000, 2),
                'duration_str' => sprintf('%.2f ms', $profile['elapse'] * 1000),
            ];
        }

        return [
            'nb_statements' => count($queries),
            'accumulated_duration' => round($totalTime * 1000, 2),
            'accumulated_duration_str' => sprintf('%.2f ms', $totalTime * 1000),
            'statements' => $queries,
        ];
    }
}
```

### Mezzio Middleware for Request Profiling

Create middleware to profile all database queries per request:

```php
use PhpDb\Adapter\Profiler\Profiler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DatabaseProfilingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Profiler $profiler
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Add profiling data to response headers in development
        if (getenv('APP_ENV') === 'development') {
            $profiles = $this->profiler->getProfiles();
            $totalTime = array_sum(array_column($profiles, 'elapse'));

            $response = $response
                ->withHeader('X-DB-Query-Count', (string) count($profiles))
                ->withHeader('X-DB-Query-Time', sprintf('%.4f', $totalTime));
        }

        return $response;
    }
}
```

### Laminas MVC Event Listener

Attach a listener to log queries after each request:

```php
use Laminas\Mvc\MvcEvent;
use PhpDb\Adapter\Profiler\Profiler;
use Psr\Log\LoggerInterface;

class DatabaseProfilerListener
{
    public function __construct(
        private Profiler $profiler,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(MvcEvent $event): void
    {
        $profiles = $this->profiler->getProfiles();

        if (empty($profiles)) {
            return;
        }

        $totalTime = array_sum(array_column($profiles, 'elapse'));
        $queryCount = count($profiles);

        $this->logger->debug('Database queries executed', [
            'count' => $queryCount,
            'total_time' => sprintf('%.4f seconds', $totalTime),
            'queries' => array_map(
                fn($p) => ['sql' => $p['sql'], 'time' => $p['elapse']],
                $profiles
            ),
        ]);
    }
}
```

Register in your module configuration:

```php
use Laminas\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $event): void
    {
        $eventManager = $event->getApplication()->getEventManager();
        $container = $event->getApplication()->getServiceManager();

        $eventManager->attach(
            MvcEvent::EVENT_FINISH,
            $container->get(DatabaseProfilerListener::class)
        );
    }
}
```

## Custom Profiler Implementation

You can create custom profilers by implementing `ProfilerInterface`:

```php
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Adapter\StatementContainerInterface;

class CustomProfiler implements ProfilerInterface
{
    private array $profiles = [];
    private int $currentIndex = 0;
    private array $currentProfile = [];

    public function profilerStart($target): self
    {
        $sql = $target instanceof StatementContainerInterface
            ? $target->getSql()
            : (string) $target;

        $this->currentProfile = [
            'sql' => $sql,
            'parameters' => $target instanceof StatementContainerInterface
                ? clone $target->getParameterContainer()
                : null,
            'start' => hrtime(true), // Use high-resolution time
            'memory_start' => memory_get_usage(true),
        ];

        return $this;
    }

    public function profilerFinish(): self
    {
        $this->currentProfile['end'] = hrtime(true);
        $this->currentProfile['memory_end'] = memory_get_usage(true);
        $this->currentProfile['elapse'] =
            ($this->currentProfile['end'] - $this->currentProfile['start']) / 1e9;
        $this->currentProfile['memory_delta'] =
            $this->currentProfile['memory_end'] - $this->currentProfile['memory_start'];

        $this->profiles[$this->currentIndex++] = $this->currentProfile;
        $this->currentProfile = [];

        return $this;
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }
}
```

## ProfilerAwareInterface

Components that can accept a profiler implement `ProfilerAwareInterface`:

```php
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

class MyDatabaseService implements ProfilerAwareInterface
{
    private ?ProfilerInterface $profiler = null;

    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        return $this;
    }

    public function executeQuery(string $sql): mixed
    {
        $this->profiler?->profilerStart($sql);

        try {
            // Execute query...
            $result = $this->doQuery($sql);
            return $result;
        } finally {
            $this->profiler?->profilerFinish();
        }
    }
}
```

## Best Practices

### Development vs Production

Only enable profiling in development environments to avoid performance overhead:

```php
use PhpDb\Adapter\Profiler\Profiler;

$profiler = null;
if (getenv('APP_ENV') === 'development') {
    $profiler = new Profiler();
}

$adapter = new Adapter($driver, $platform, $resultSetPrototype, $profiler);
```

### Memory Considerations

The profiler stores all query profiles in memory. For long-running processes
or batch operations, consider periodically clearing or limiting profiles:

```php
class LimitedProfiler extends Profiler
{
    private int $maxProfiles;

    public function __construct(int $maxProfiles = 100)
    {
        $this->maxProfiles = $maxProfiles;
    }

    public function profilerFinish(): self
    {
        parent::profilerFinish();

        // Keep only the last N profiles
        if (count($this->profiles) > $this->maxProfiles) {
            $this->profiles = array_slice(
                $this->profiles,
                -$this->maxProfiles,
                preserve_keys: false
            );
            $this->currentIndex = count($this->profiles);
        }

        return $this;
    }
}
```

### Combining with Query Logging

For comprehensive debugging, combine profiling with SQL logging:

```php
use Psr\Log\LoggerInterface;

class LoggingProfiler extends Profiler
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $logAllQueries = false
    ) {
    }

    public function profilerFinish(): self
    {
        parent::profilerFinish();

        $profile = $this->getLastProfile();

        if ($this->logAllQueries) {
            $this->logger->debug('Query executed', [
                'sql' => $profile['sql'],
                'time' => sprintf('%.4f seconds', $profile['elapse']),
            ]);
        }

        return $this;
    }
}
```
