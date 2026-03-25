# Test Coverage & Refactoring Log

## Current State (2026-03-25)

- **Tests:** 1439 passing, 0 skipped, 0 warnings
- **Coverage:** 99.13% lines (3289/3318), up from 84%
- **Sql/\*:** 100% (1649/1649)
- **ResultSet/\*:** 100% (121/121)
- **Metadata/\*:** 100% (204/204)
- **Container/\*:** 100% (83/83)
- **Adapter/\*:** remaining gap — 29 uncovered lines across 11 files

---

## Completed Work

### Dead Code Removed

| File | What was removed | Reason |
|---|---|---|
| `Sql/AbstractSql.php` | `processValuesArgument()` method + `Values` match arm (~29 lines) | `flattenExpressionValues()` always expands Values before the match fires |
| `Sql/AbstractSql.php` | Defensive throw in `processJoin()` for invalid name type (~5 lines) | Type system on `Join::join()` prevents invalid types |
| `SelectTest.php` | `testBadJoinName` test | Tested the removed throw |
| `ResultSet/AbstractResultSet.php` | Throw in `initialize()` for invalid data source (3 lines) | PHP 8 `iterable` = `array\|Traversable`, both handled |
| `ResultSet/AbstractResultSet.php` | Non-Iterator branch in `valid()` (3 lines) | `initialize()` always stores an Iterator |
| `ResultSet/AbstractResultSet.php` | Non-Iterator branch in `rewind()` (2 lines) | Same reason |

### Skipped Tests Removed (9 total)

| Test | Reason for removal |
|---|---|
| `AdapterInterfaceDelegatorTest::testDelegatorWithPluginManager` | `$options` param is dead code in delegator |
| `ConnectionTest::testResource` | Required concrete driver DSN building that doesn't exist |
| `ConnectionTest::testArrayOfConnectionParametersCreatesCorrectDsn` | Required MySQL-specific DSN building |
| `ConnectionTest::testHostnameAndUnixSocketThrowsInvalidConnectionParametersException` | Required MySQL parameter validation |
| `ConnectionTest::testDblibArrayOfConnectionParametersCreatesCorrectDsn` | Required Dblib-specific DSN building |
| `PlatformTest::testAbstractPlatformCrashesGracefullyOnMissingDefaultPlatform` | Empty stub, readonly skip reason outdated |
| `PlatformTest::testAbstractPlatformCrashesGracefullyOnMissingDefaultPlatformWithGetDecorators` | Empty stub, readonly skip reason outdated |
| `PredicateTest::testCanCreateExpressionsWithoutAnyBoundSqlParameters` | Contradictory logic, behaviour covered elsewhere |
| `MetadataFeatureTest::testPostInitialize` | Redundant, 6 other tests cover same behaviour |

### Anonymous Classes Replaced with TestAssets

| TestAsset | Replaces | Location |
|---|---|---|
| `TestSql92Platform` | 3 anonymous Sql92 subclasses | `test/unit/TestAsset/` |
| `TestTableGatewayFeature` | 7 anonymous TG features | `test/unit/TableGateway/Feature/TestAsset/` |
| `TestRowGatewayFeature` | 3 anonymous RG features | `test/unit/RowGateway/Feature/TestAsset/` |
| `TestDriverFeature` | 1 anonymous Driver\Feature\AbstractFeature | `test/unit/Adapter/Driver/Feature/TestAsset/` |
| `ConcreteTableObject` | 1 anonymous AbstractTableObject | `test/unit/Metadata/Object/TestAsset/` |
| `TestTableGateway` | 1 anonymous TableGateway | `test/unit/TableGateway/Feature/TestAsset/` |
| `TestPluginManager` | 1 anonymous AbstractPluginManager | `test/unit/Adapter/Container/TestAsset/` |
| `TestFeatureDriver` | 1 anonymous DriverInterface+Trait impl | `test/unit/Adapter/Driver/TestAsset/` |
| `IncompleteSource` | (new) for testing incomplete subclass | `test/unit/Metadata/Source/TestAsset/` |

`ConcreteAdapterAwareObject` (pre-existing) replaced 2 anonymous AdapterAwareTrait classes.
`Sql\Platform\AbstractPlatform` used directly (not abstract despite name).

### CoversMethod Fixes

- Removed invalid `Adapter::createDriver`, `Adapter::createPlatform` (methods deleted)
- Removed invalid `Join::__construct` (no constructor)
- Removed invalid `AbstractSql::processExpressionValue` (method deleted)
- Removed invalid `Argument::__construct` etc. (factory class, no such methods)
- Fixed `ConnectionTransactionsTest` — removed `()` from method name strings

### Infrastructure

- PCOV removed, Xdebug installed for PHP 8.1/8.3/8.4
- `xdebug.mode=coverage` configured in `conf.d/ext-xdebug.ini` for all versions

---

## Remaining Work: Adapter/\* to 100%

29 uncovered lines across 11 files. All are in `src/Adapter/`.

### Files with uncovered lines

| File | Covered | Uncovered lines | What needs testing |
|---|---|---|---|
| `Adapter.php` | 58/61 | 38, 158, 163 | L38: `setProfiler` delegation when driver is `ProfilerAwareInterface`. L158/163: closures returned by `getHelpers()` need to be called, not just returned |
| `AdapterAwareTrait.php` | 0/2 | 12, 14 | `setDbAdapter()` body. Likely a `#[CoversMethod]` attribution issue — `AdapterAwareTraitTest` calls it but may not list it |
| `ParameterContainer.php` | 68/85 | 131, 140, 151, 177, 199, 212, 215, 226, 239, 242, 263, 276, 279, 290, 303, 306, 338 | L131: `offsetSet` with int name not in positions. L140: nameMapping match. L151: invalid key throw. L177: `offsetUnset` positions. L199-338: maxlength/errata method branches. L338: `getPositionalArray`. Most are likely `#[CoversMethod]` attribution — check if methods are listed |
| `Driver/AbstractConnection.php` | 4/14 | 45, 51, 56, 65, 66, 69, 81, 83, 90, 92 | ALL methods uncovered. Need `test/unit/Adapter/Driver/AbstractConnectionTest.php` — use `ConnectionWrapper` test asset |
| `Driver/Feature/DriverFeatureProviderTrait.php` | 8/13 | 30, 31, 32, 33, 34 | L30-34: `addFeature` throw when trait not in DriverInterface. Create a class using the trait WITHOUT implementing DriverInterface |
| `Driver/Pdo/AbstractPdo.php` | 26/41 | 47, 100, 138-157 | L47: constructor `addFeatures`. L100: `createStatement` with PDOStatement. L138-157: `formatParameterName` branches — int name with NAMED type, positional return |
| `Driver/Pdo/AbstractPdoConnection.php` | 43/52 | 73-75, 101, 119, 171, 192-196 | L73-75: `getDsn` throws (user already added this test via linter). L101/119/171: auto-connect in beginTransaction/commit/execute (user already added these). L192-196: `prepare` auto-connect and delegate (user already added) |
| `Driver/Pdo/Result.php` | 41/48 | 109, 125-128, 131, 238 | L109: `buffer()` empty body. L125-128: `setFetchMode` invalid throw. L131: valid setFetchMode assignment. L238: `valid()` return. Likely `#[CoversMethod]` issues — check if methods are listed |
| `Driver/Pdo/Statement.php` | 73/87 | 50, 125, 161-162, 186, 205, 213-218, 238, 254 | L50: constructor body. L125: prepare-already-prepared throw. L161-162: execute param merging. L186: error code cast. L205: bindParams early return. L213-218: errata type matching. L238: positional binding. L254: clone. Many likely `#[CoversMethod]` issues |
| `Platform/AbstractPlatform.php` | 32/38 | 126-130, 132 | `quoteValue()` without driver throws `VunerablePlatformQuoteException`. Already tested in `Sql92Test::testQuoteValue` but may not be attributed to `AbstractPlatform` |
| `Profiler/Profiler.php` | 27/30 | 45, 46, 47 | `profilerStart` throw on invalid target type. May be tested via TypeError already due to type hints |

### Strategy for remaining work

Many of these uncovered lines fall into two categories:

1. **`#[CoversMethod]` attribution gaps** — the code IS executed by tests but the test class doesn't list the method in its CoversMethod attributes, so coverage isn't credited. Fix: add missing attributes.

2. **Actually untested branches** — specific code paths not exercised. Fix: add targeted tests.

Start by checking CoversMethod on each test file (ParameterContainerTest, ResultTest, StatementTest, Sql92Test, ProfilerTest, AdapterAwareTraitTest). Many lines will light up just by adding the attribute.

For `AbstractConnection`, `AbstractPdo`, and `DriverFeatureProviderTrait`, actual new tests are needed since the existing tests don't exercise the uncovered paths.

The `ConnectionTest.php` file was updated by the user with tests for getDsn throw, beginTransaction/commit/execute auto-connect, and prepare — these should cover most of `AbstractPdoConnection.php` when the Clover report is regenerated.
