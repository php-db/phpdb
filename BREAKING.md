# PhpDb vs Laminas\Db - Breaking Changes

This document lists all breaking changes (BC breaks) between PhpDb and Laminas\Db SQL classes.

> **Note:** Some breaking changes have been mitigated with a **BC Compatibility Layer** that provides
> deprecated methods. These are marked with ✅ below. The deprecated methods emit `E_USER_DEPRECATED`
> notices to guide migration to the new API.

---

## Select

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `setSpecification()` removed | HIGH | ❌ | Public method removed entirely |
| All `process*()` methods removed | HIGH | ❌ | Breaks classes extending Select |
| `$specifications` property removed | HIGH | ❌ | Breaks inheritance customization |
| `JOIN_OUTER_LEFT`, `JOIN_OUTER_RIGHT` constants removed | MEDIUM | ❌ | Deprecated constants removed |
| All constants now `final` | MEDIUM | ❌ | Cannot override in subclasses |
| `$columns` → `?Columns` object | HIGH | ❌ | Property type changed |
| `$order` → `?Order` object | HIGH | ❌ | Property type changed |
| `$group` → `?Group` object | HIGH | ❌ | Property type changed |
| `$limit` → `?Limit` object | HIGH | ❌ | Property type changed |
| `$offset` → `?Offset` object | HIGH | ❌ | Property type changed |
| All methods have strict type hints | MEDIUM | ❌ | Stricter type checking |
| Return types `$this` → `static` | LOW | ❌ | Minor semantic change |

---

## Insert

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `SPECIFICATION_INSERT` constant removed | HIGH | ❌ | Constant no longer exists |
| `SPECIFICATION_SELECT` constant removed | HIGH | ❌ | Constant no longer exists |
| `getRawState()` structure changed | HIGH | ❌ | Returns `Values` object, removed `columns` key |
| `__set()` returns `void` not `$this` | HIGH | ❌ | Breaks fluent chaining via magic setter |
| `processInsert()` → `buildInsertValuesSql()` | HIGH | ❌ | Protected method renamed |
| `processSelect()` → `buildInsertSelectSql()` | HIGH | ❌ | Protected method renamed |
| `$columns` → `?Values` object | HIGH | ❌ | Property type changed |
| `$specifications` property removed | HIGH | ❌ | Breaks inheritance |
| All methods have strict type hints | MEDIUM | ❌ | Stricter type checking |

---

## Update

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `SPECIFICATION_UPDATE` constant removed | HIGH | ❌ | Constant no longer exists |
| `SPECIFICATION_SET` constant removed | HIGH | ❌ | Constant no longer exists |
| `SPECIFICATION_WHERE` constant removed | HIGH | ❌ | Constant no longer exists |
| `SPECIFICATION_JOIN` constant removed | HIGH | ❌ | Constant no longer exists |
| `getRawState()` removed `emptyWhereProtection` | MEDIUM | ❌ | Key no longer in raw state |
| `getRawState()` `set` returns `Set` object | HIGH | ❌ | Was array via `->toArray()` |
| `$emptyWhereProtection` property removed | HIGH | ❌ | Use `->where->setEmptyAllowed()` |
| `$set` → `?Set` object | HIGH | ❌ | Was `PriorityList` |
| All `process*()` methods removed | HIGH | ❌ | Replaced with `build*()` |
| `$specifications` property removed | HIGH | ❌ | Breaks inheritance |
| Empty WHERE throws exception by default | HIGH | ❌ | Different protection mechanism |
| All methods have strict type hints | MEDIUM | ❌ | Stricter type checking |

---

## Delete

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `SPECIFICATION_DELETE` constant removed | HIGH | ❌ | Constant no longer exists |
| `SPECIFICATION_WHERE` constant removed | HIGH | ❌ | Constant no longer exists |
| `getRawState()` removed `emptyWhereProtection` | MEDIUM | ❌ | Key no longer in raw state |
| `getRawState()` removed `set` key | MEDIUM | ❌ | Key no longer exists |
| `$emptyWhereProtection` property removed | HIGH | ❌ | Use `->where->setEmptyAllowed()` |
| `processDelete()` method removed | HIGH | ❌ | Breaks inheritance |
| `processWhere()` method removed | HIGH | ❌ | Breaks inheritance |
| `$specifications` property removed | HIGH | ❌ | Breaks inheritance |
| Empty WHERE throws exception by default | HIGH | ❌ | Different protection mechanism |
| All methods have strict type hints | MEDIUM | ❌ | Stricter type checking |

---

## TableIdentifier

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `setTable()` removed | HIGH | ✅ **RESTORED** | Returns new immutable instance (deprecated) |
| `setSchema()` removed | HIGH | ✅ **RESTORED** | Returns new immutable instance (deprecated) |
| `hasSchema()` removed | MEDIUM | ✅ **RESTORED** | Alias for `getSchema() !== null` (deprecated) |
| Object is immutable (`readonly`) | HIGH | ❌ | Cannot modify after construction |
| Constructor rejects `__toString()` objects | MEDIUM | ❌ | Must pass string explicitly |

---

## Join

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `$on` accepts `PredicateInterface` not `Expression` | MEDIUM | ❌ | Type change for join condition |
| All constants now `final` | MEDIUM | ❌ | Cannot override in subclasses |
| All Iterator methods have return types | LOW | ❌ | `void`, `array`, `int`, `bool` |
| All methods have strict type hints | MEDIUM | ❌ | Stricter type checking |

---

## Where / Having / Predicate

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| `equalTo()`, etc. - `$leftType`/`$rightType` removed | **CRITICAL** | ✅ **RESTORED** | Optional params restored (deprecated) |
| `literal()` - `$parameters` argument removed | **CRITICAL** | ✅ **RESTORED** | Redirects to `expression()` (deprecated) |
| `TYPE_IDENTIFIER`, `TYPE_VALUE`, `TYPE_LITERAL` constants | **CRITICAL** | ✅ **RESTORED** | Constants restored (deprecated) |
| `in()`/`notIn()` - `$valueSet` now required | HIGH | ❌ | Was optional |
| `in()`/`notIn()` - doesn't accept `Select` | HIGH | ❌ | Only `array\|ArgumentInterface` |
| `getExpressionData()` return structure changed | **CRITICAL** | ❌ | Returns `['spec'=>..., 'values'=>...]` |
| `__get()` case-sensitive | MEDIUM | ❌ | No more `strtolower()` |
| New: `setEmptyAllowed()` / `isEmptyAllowed()` | N/A | ❌ | New methods on Where |

---

## Expression

| Change | Severity | BC Layer | Details |
|--------|----------|----------|---------|
| Constructor `$types` parameter removed | **CRITICAL** | ❌ | Third argument no longer exists |
| `setTypes()` method removed | **CRITICAL** | ❌ | Deprecated method removed |
| `getTypes()` method removed | **CRITICAL** | ❌ | Deprecated method removed |
| `getExpressionData()` return structure changed | **CRITICAL** | ❌ | Returns `['spec'=>..., 'values'=>...]` |
| `PLACEHOLDER` constant now `final` | MEDIUM | ❌ | Cannot override |
| Parameters wrapped in `ArgumentInterface` | HIGH | ❌ | Internal representation changed |

---

## Overall Architecture Changes

1. **Specifications removed** - All `$specifications` arrays and `process*()` methods replaced with direct `buildSqlString()` concatenation
2. **Value objects** - Primitive properties replaced with dedicated classes (`Columns`, `Order`, `Group`, `Limit`, `Offset`, `Set`, `Values`)
3. **Strict typing** - All public methods now have parameter and return type declarations
4. **Immutability** - `TableIdentifier` is now immutable with `readonly` properties
5. **Empty WHERE protection** - Moved from boolean flag to `Where::setEmptyAllowed()`
6. **`getExpressionData()` format** - Changed from nested indexed arrays to `['spec' => ..., 'values' => ...]`

---

## BC Compatibility Layer

The following deprecated methods/features are available for easier migration. They emit `E_USER_DEPRECATED` notices:

### Predicate Type Parameters

The `$leftType` and `$rightType` parameters have been restored to comparison methods:

```php
// This now works (with deprecation notice):
$where->equalTo('id', $value, Predicate::TYPE_IDENTIFIER, Predicate::TYPE_VALUE);
$where->notEqualTo('col1', 'col2', Predicate::TYPE_IDENTIFIER, Predicate::TYPE_IDENTIFIER);
$where->lessThan('price', 100, Predicate::TYPE_IDENTIFIER, Predicate::TYPE_VALUE);
// ... and all other comparison methods
```

Available type constants (deprecated):
- `Predicate::TYPE_IDENTIFIER` - Treat value as column/identifier
- `Predicate::TYPE_VALUE` - Treat value as bound parameter
- `Predicate::TYPE_LITERAL` - Treat value as literal SQL

### Literal with Parameters

The `$parameters` argument to `literal()` has been restored:

```php
// This now works (with deprecation notice):
$where->literal('created_at > ?', $date);

// Internally redirects to:
$where->expression('created_at > ?', $date);
```

### TableIdentifier Methods

The setter methods have been restored (returning new immutable instances):

```php
// These now work (with deprecation notices):
$table = new TableIdentifier('users');
$table = $table->setTable('accounts');   // Returns NEW instance
$table = $table->setSchema('public');    // Returns NEW instance
if ($table->hasSchema()) { ... }         // Alias for getSchema() !== null
```

---

## Migration Priorities

### Must fix immediately (no BC layer)

1. Code parsing `getExpressionData()` return values
2. Code using `setTypes()`/`getTypes()` on Expression
3. Code using `SPECIFICATION_*` constants
4. Code extending SQL classes and overriding `process*()` methods

### Can migrate gradually (BC layer available)

1. ✅ Code using `$leftType`/`$rightType` in predicate comparisons
2. ✅ Code using `literal()` with parameters
3. ✅ Code using `TableIdentifier::setTable()`/`setSchema()`/`hasSchema()`

### Should fix when convenient

1. Code relying on `$emptyWhereProtection` property
2. Code expecting `getRawState()` to return primitives instead of value objects

---

## Migration Examples

### Empty WHERE protection

**Before (Laminas):**
```php
$delete = new Delete('users');
$delete->emptyWhereProtection(false);
$delete->where(['active' => 0]);
```

**After (PhpDb):**
```php
$delete = new Delete('users');
$delete->where->setEmptyAllowed(); // If you want to allow empty WHERE
$delete->where(['active' => 0]);
```

### TableIdentifier

**Before (Laminas) - Still works with deprecation notice:**
```php
$table = new TableIdentifier('users');
$table = $table->setSchema('public');  // Note: must capture return value!
if ($table->hasSchema()) { ... }
```

**After (PhpDb) - Recommended:**
```php
$table = new TableIdentifier('users', 'public');
// Or: TableIdentifier::from(['alias' => 'users'], schema: 'public')
if ($table->getSchema() !== null) { ... }
```

### Predicate type parameters

**Before (Laminas) - Still works with deprecation notice:**
```php
$where->equalTo('id', $value, Predicate::TYPE_IDENTIFIER, Predicate::TYPE_VALUE);
```

**After (PhpDb) - Recommended:**
```php
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;

$where->equalTo(new Identifier('id'), new Value($value));
// Or simply (defaults to identifier = value):
$where->equalTo('id', $value);
```

### Literal with parameters

**Before (Laminas) - Still works with deprecation notice:**
```php
$where->literal('created_at > ?', $date);
```

**After (PhpDb) - Recommended:**
```php
$where->expression('created_at > ?', $date);
```

---

## Suppressing Deprecation Notices

During migration, you can suppress deprecation notices if needed:

```php
// Suppress all deprecation notices (not recommended for production)
error_reporting(error_reporting() & ~E_USER_DEPRECATED);

// Or handle them with a custom error handler
set_error_handler(function ($errno, $errstr) {
    if ($errno === E_USER_DEPRECATED && str_contains($errstr, 'PhpDb')) {
        // Log it, ignore it, or handle as needed
        return true;
    }
    return false;
});
```
