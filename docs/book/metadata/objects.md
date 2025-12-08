# Metadata Value Objects

Metadata returns value objects that provide an interface to help developers
better explore the metadata. Below is the API for the various value objects:

## TableObject

`TableObject` extends `AbstractTableObject` and represents a database table:

```php title="TableObject Class Definition"
class PhpDb\Metadata\Object\TableObject extends AbstractTableObject
{
    public function __construct(?string $name = null);
    public function setColumns(array $columns): void;
    public function getColumns(): ?array;
    public function setConstraints(array $constraints): void;
    public function getConstraints(): ?array;
    public function setName(string $name): void;
    public function getName(): ?string;
}
```

## ColumnObject

All setter methods return `static` for fluent interface support:

```php title="ColumnObject Class Definition"
class PhpDb\Metadata\Object\ColumnObject
{
    public function __construct(string $name, string $tableName, ?string $schemaName = null);

    public function setName(string $name): void;
    public function getName(): string;

    public function getTableName(): string;
    public function setTableName(string $tableName): static;

    public function setSchemaName(string $schemaName): void;
    public function getSchemaName(): ?string;

    public function getOrdinalPosition(): ?int;
    public function setOrdinalPosition(?int $ordinalPosition): static;

    public function getColumnDefault(): ?string;
    public function setColumnDefault(null|string|int|bool $columnDefault): static;

    public function getIsNullable(): ?bool;
    public function setIsNullable(?bool $isNullable): static;
    public function isNullable(): ?bool;  // Alias for getIsNullable()

    public function getDataType(): ?string;
    public function setDataType(string $dataType): static;

    public function getCharacterMaximumLength(): ?int;
    public function setCharacterMaximumLength(?int $characterMaximumLength): static;

    public function getCharacterOctetLength(): ?int;
    public function setCharacterOctetLength(?int $characterOctetLength): static;

    public function getNumericPrecision(): ?int;
    public function setNumericPrecision(?int $numericPrecision): static;

    public function getNumericScale(): ?int;
    public function setNumericScale(?int $numericScale): static;

    public function getNumericUnsigned(): ?bool;
    public function setNumericUnsigned(?bool $numericUnsigned): static;
    public function isNumericUnsigned(): ?bool;  // Alias for getNumericUnsigned()

    public function getErratas(): array;
    public function setErratas(array $erratas): static;

    public function getErrata(string $errataName): mixed;
    public function setErrata(string $errataName, mixed $errataValue): static;
}
```

## ConstraintObject

All setter methods return `static` for fluent interface support:

```php title="ConstraintObject Class Definition"
class PhpDb\Metadata\Object\ConstraintObject
{
    public function __construct(string $name, string $tableName, ?string $schemaName = null);

    public function setName(string $name): void;
    public function getName(): string;

    public function setSchemaName(string $schemaName): void;
    public function getSchemaName(): ?string;

    public function getTableName(): string;
    public function setTableName(string $tableName): static;

    public function setType(string $type): void;
    public function getType(): ?string;

    public function hasColumns(): bool;
    public function getColumns(): array;
    public function setColumns(array $columns): static;

    public function getReferencedTableSchema(): ?string;
    public function setReferencedTableSchema(string $referencedTableSchema): static;

    public function getReferencedTableName(): ?string;
    public function setReferencedTableName(string $referencedTableName): static;

    public function getReferencedColumns(): ?array;
    public function setReferencedColumns(array $referencedColumns): static;

    public function getMatchOption(): ?string;
    public function setMatchOption(string $matchOption): static;

    public function getUpdateRule(): ?string;
    public function setUpdateRule(string $updateRule): static;

    public function getDeleteRule(): ?string;
    public function setDeleteRule(string $deleteRule): static;

    public function getCheckClause(): ?string;
    public function setCheckClause(string $checkClause): static;

    // Type checking methods
    public function isPrimaryKey(): bool;
    public function isUnique(): bool;
    public function isForeignKey(): bool;
    public function isCheck(): bool;
}
```

## ViewObject

The `ViewObject` extends `AbstractTableObject` and represents database views. It
includes all methods from `TableObject` plus view-specific properties:

```php title="ViewObject Class Definition"
class PhpDb\Metadata\Object\ViewObject extends AbstractTableObject
{
    public function __construct(?string $name = null);
    public function setName(string $name): void;
    public function getName(): ?string;
    public function setColumns(array $columns): void;
    public function getColumns(): ?array;
    public function setConstraints(array $constraints): void;
    public function getConstraints(): ?array;

    public function getViewDefinition(): ?string;
    public function setViewDefinition(?string $viewDefinition): static;

    public function getCheckOption(): ?string;
    public function setCheckOption(?string $checkOption): static;

    public function getIsUpdatable(): ?bool;
    public function isUpdatable(): ?bool;
    public function setIsUpdatable(?bool $isUpdatable): static;
}
```

The `getViewDefinition()` method returns the SQL that creates the view:

```php title="Retrieving View Definition"
$view = $metadata->getView('active_users');
echo $view->getViewDefinition();
```

Outputs:

```sql title="View Definition SQL Output"
SELECT id, name, email FROM users WHERE status = 'active'
```

The `getCheckOption()` returns the view's check option:

- `CASCADED` - Checks for updatability cascade to underlying views
- `LOCAL` - Only checks this view for updatability
- `NONE` - No check option specified

The `isUpdatable()` method (alias for `getIsUpdatable()`) indicates whether the
view supports INSERT, UPDATE, or DELETE operations.

## ConstraintKeyObject

The `ConstraintKeyObject` provides detailed information about individual columns
participating in constraints, particularly useful for foreign key relationships:

```php title="ConstraintKeyObject Class Definition"
class PhpDb\Metadata\Object\ConstraintKeyObject
{
    public const FK_CASCADE = 'CASCADE';
    public const FK_SET_NULL = 'SET NULL';
    public const FK_NO_ACTION = 'NO ACTION';
    public const FK_RESTRICT = 'RESTRICT';
    public const FK_SET_DEFAULT = 'SET DEFAULT';

    public function __construct(string $column);

    public function getColumnName(): string;
    public function setColumnName(string $columnName): static;

    public function getOrdinalPosition(): ?int;
    public function setOrdinalPosition(int $ordinalPosition): static;

    public function getPositionInUniqueConstraint(): ?bool;
    public function setPositionInUniqueConstraint(bool $positionInUniqueConstraint): static;

    public function getReferencedTableSchema(): ?string;
    public function setReferencedTableSchema(string $referencedTableSchema): static;

    public function getReferencedTableName(): ?string;
    public function setReferencedTableName(string $referencedTableName): static;

    public function getReferencedColumnName(): ?string;
    public function setReferencedColumnName(string $referencedColumnName): static;

    public function getForeignKeyUpdateRule(): ?string;
    public function setForeignKeyUpdateRule(string $foreignKeyUpdateRule): void;

    public function getForeignKeyDeleteRule(): ?string;
    public function setForeignKeyDeleteRule(string $foreignKeyDeleteRule): void;
}
```

Constraint keys are retrieved using `getConstraintKeys()`:

```php title="Iterating Through Foreign Key Constraint Details"
$keys = $metadata->getConstraintKeys('fk_orders_customers', 'orders');
foreach ($keys as $key) {
    echo $key->getColumnName() . ' -> '
         . $key->getReferencedTableName() . '.'
         . $key->getReferencedColumnName() . PHP_EOL;
    echo '  ON UPDATE: ' . $key->getForeignKeyUpdateRule() . PHP_EOL;
    echo '  ON DELETE: ' . $key->getForeignKeyDeleteRule() . PHP_EOL;
}
```

Outputs:

```text title="Foreign Key Constraint Output"
customer_id -> customers.id
  ON UPDATE: CASCADE
  ON DELETE: RESTRICT
```

## TriggerObject

All setter methods return `static` for fluent interface support:

```php title="TriggerObject Class Definition"
class PhpDb\Metadata\Object\TriggerObject
{
    public function getName(): ?string;
    public function setName(string $name): static;

    public function getEventManipulation(): ?string;
    public function setEventManipulation(string $eventManipulation): static;

    public function getEventObjectCatalog(): ?string;
    public function setEventObjectCatalog(string $eventObjectCatalog): static;

    public function getEventObjectSchema(): ?string;
    public function setEventObjectSchema(string $eventObjectSchema): static;

    public function getEventObjectTable(): ?string;
    public function setEventObjectTable(string $eventObjectTable): static;

    public function getActionOrder(): ?string;
    public function setActionOrder(string $actionOrder): static;

    public function getActionCondition(): ?string;
    public function setActionCondition(?string $actionCondition): static;

    public function getActionStatement(): ?string;
    public function setActionStatement(string $actionStatement): static;

    public function getActionOrientation(): ?string;
    public function setActionOrientation(string $actionOrientation): static;

    public function getActionTiming(): ?string;
    public function setActionTiming(string $actionTiming): static;

    public function getActionReferenceOldTable(): ?string;
    public function setActionReferenceOldTable(?string $actionReferenceOldTable): static;

    public function getActionReferenceNewTable(): ?string;
    public function setActionReferenceNewTable(?string $actionReferenceNewTable): static;

    public function getActionReferenceOldRow(): ?string;
    public function setActionReferenceOldRow(string $actionReferenceOldRow): static;

    public function getActionReferenceNewRow(): ?string;
    public function setActionReferenceNewRow(string $actionReferenceNewRow): static;

    public function getCreated(): ?DateTime;
    public function setCreated(?DateTime $created): static;
}
```
