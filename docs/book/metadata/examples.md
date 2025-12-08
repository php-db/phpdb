# Metadata Examples and Troubleshooting

## Common Patterns and Best Practices

```php title="Finding All Tables with a Specific Column"
function findTablesWithColumn(
    MetadataInterface $metadata,
    string $columnName
): array {
    $tables = [];
    foreach ($metadata->getTableNames() as $tableName) {
        $columnNames = $metadata->getColumnNames($tableName);
        if (in_array($columnName, $columnNames, true)) {
            $tables[] = $tableName;
        }
    }
    return $tables;
}

$tablesWithUserId = findTablesWithColumn($metadata, 'user_id');
```

```php title="Discovering Foreign Key Relationships"
function getForeignKeyRelationships(
    MetadataInterface $metadata,
    string $tableName
): array {
    $relationships = [];
    $constraints = $metadata->getConstraints($tableName);

    foreach ($constraints as $constraint) {
        if (! $constraint->isForeignKey()) {
            continue;
        }

        $relationships[] = [
            'constraint' => $constraint->getName(),
            'columns' => $constraint->getColumns(),
            'references' => $constraint->getReferencedTableName(),
            'referenced_columns' => $constraint->getReferencedColumns(),
            'on_update' => $constraint->getUpdateRule(),
            'on_delete' => $constraint->getDeleteRule(),
        ];
    }

    return $relationships;
}
```

```php title="Generating Schema Documentation"
function generateTableDocumentation(
    MetadataInterface $metadata,
    string $tableName
): string {
    $table = $metadata->getTable($tableName);
    $doc = "# Table: $tableName\n\n";

    $doc .= "## Columns\n\n";
    $doc .= "| Column | Type | Nullable | Default |\n";
    $doc .= "|--------|------|----------|--------|\n";

    foreach ($table->getColumns() as $column) {
        $type = $column->getDataType();
        if ($column->getCharacterMaximumLength()) {
            $type .= '(' . $column->getCharacterMaximumLength() . ')';
        } elseif ($column->getNumericPrecision()) {
            $type .= '(' . $column->getNumericPrecision();
            if ($column->getNumericScale()) {
                $type .= ',' . $column->getNumericScale();
            }
            $type .= ')';
        }

        $nullable = $column->isNullable() ? 'YES' : 'NO';
        $default = $column->getColumnDefault() ?? 'NULL';

        $doc .= "| {$column->getName()} | $type | $nullable | $default |\n";
    }

    $doc .= "\n## Constraints\n\n";
    $constraints = $metadata->getConstraints($tableName);

    foreach ($constraints as $constraint) {
        $doc .= "- **{$constraint->getName()}** ";
        $doc .= "({$constraint->getType()})\n";
        if ($constraint->hasColumns()) {
            $doc .= "  - Columns: " .
                implode(', ', $constraint->getColumns()) . "\n";
        }
        if ($constraint->isForeignKey()) {
            $doc .= "  - References: ";
            $doc .= "{$constraint->getReferencedTableName()}";
            $doc .= "(" .
                implode(', ', $constraint->getReferencedColumns()) .
                ")\n";
            $doc .= "  - ON UPDATE: ";
            $doc .= "{$constraint->getUpdateRule()}\n";
            $doc .= "  - ON DELETE: ";
            $doc .= "{$constraint->getDeleteRule()}\n";
        }
    }

    return $doc;
}
```

```php title="Comparing Schemas Across Environments"
function compareTables(
    MetadataInterface $metadata1,
    MetadataInterface $metadata2,
    string $tableName
): array {
    $differences = [];

    $columns1 = $metadata1->getColumnNames($tableName);
    $columns2 = $metadata2->getColumnNames($tableName);

    $missing = array_diff($columns1, $columns2);
    if ($missing) {
        $differences['missing_columns'] = $missing;
    }

    $extra = array_diff($columns2, $columns1);
    if ($extra) {
        $differences['extra_columns'] = $extra;
    }

    return $differences;
}
```

```php title="Generating Entity Classes from Metadata"
function generateEntityClass(
    MetadataInterface $metadata,
    string $tableName
): string {
    $columns = $metadata->getColumns($tableName);
    $className = str_replace(
        ' ',
        '',
        ucwords(str_replace('_', ' ', $tableName))
    );

    $code = "<?php\n\nclass {$className}\n{\n";

    foreach ($columns as $column) {
        $type = match ($column->getDataType()) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint'
                => 'int',
            'decimal', 'float', 'double', 'real' => 'float',
            'bool', 'boolean' => 'bool',
            default => 'string',
        };

        $nullable = $column->isNullable() ? '?' : '';
        $property = lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $column->getName()))
            )
        );

        $code .= "    private {$nullable}{$type} ";
        $code .= "\${$property};\n";
    }

    $code .= "}\n";
    return $code;
}
```

## Error Handling

Metadata methods throw `\Exception` when objects are not found:

```php
try {
    $table = $metadata->getTable('nonexistent_table');
} catch (Exception $e) {
    // Handle error
}
```

**Exception messages by method:**

| Method | Message |
| ------ | ------- |
| `getTable()` | Table "name" does not exist |
| `getView()` | View "name" does not exist |
| `getColumn()` | A column by that name was not found |
| `getConstraint()` | Cannot find a constraint by that name |
| `getTrigger()` | Trigger "name" does not exist |

**Best practice:** Check existence first using `getTableNames()`,
`getColumnNames()`, etc:

```php
if (in_array('users', $metadata->getTableNames(), true)) {
    $table = $metadata->getTable('users');
}
```

### Performance with Large Schemas

When working with databases that have hundreds of tables, use
`get*Names()` methods instead of retrieving full objects:

```php title="Efficient Metadata Access for Large Schemas"
$tableNames = $metadata->getTableNames();
foreach ($tableNames as $tableName) {
    $columnNames = $metadata->getColumnNames($tableName);
}
```

This is more efficient than:

```php title="Inefficient Metadata Access Pattern"
$tables = $metadata->getTables();
foreach ($tables as $table) {
    $columns = $table->getColumns();
}
```

### Schema Permission Issues

If you encounter errors accessing certain tables or schemas, verify database
user permissions:

```php title="Verifying Schema Access Permissions"
try {
    $tables = $metadata->getTableNames('restricted_schema');
} catch (Exception $e) {
    echo 'Access denied or schema does not exist';
}
```

### Caching Metadata

The metadata component queries the database each time a method is called.
For better performance in production, consider caching the results:

```php title="Implementing Metadata Caching"
$cache = $container->get('cache');
$cacheKey = 'metadata_tables';

$tables = $cache->get($cacheKey);
if ($tables === null) {
    $tables = $metadata->getTables();
    $cache->set($cacheKey, $tables, 3600);
}
```

## Platform-Specific Behavior

### MySQL

- View definitions include `SELECT` statement exactly as stored
- Supports `AUTO_INCREMENT` in column errata
- Trigger support is comprehensive with full INFORMATION_SCHEMA access
- Check constraints available in MySQL 8.0+

### PostgreSQL

- Schema support is robust, multiple schemas are common
- View `check_option` is well-supported
- Detailed trigger information including conditions
- Sequence information available in column errata

### SQLite

- Limited schema support (single default schema)
- View definitions may be formatted differently
- Trigger support varies by SQLite version
- Foreign key enforcement must be enabled separately

### SQL Server

- Schema support is robust with `dbo` as default schema
- View definitions may include schema qualifiers
- Trigger information may have platform-specific fields
- Constraint types may include platform-specific values
