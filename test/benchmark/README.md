# PhpDb Benchmark Suite

Comparative benchmark suite measuring performance of PhpDb against Laminas Db and Doctrine DBAL.

## Prerequisites

1. **MySQL Database**: The benchmarks use the Sakila sample database.
2. **PHP 8.2+**: Required for all libraries.

## Database Setup

1. Create the database and user:

```sql
CREATE DATABASE phpdb;
CREATE USER 'phpdb'@'localhost' IDENTIFIED BY 'phpdb';
GRANT ALL PRIVILEGES ON phpdb.* TO 'phpdb'@'localhost';
FLUSH PRIVILEGES;
```

2. Load the Sakila schema and data:

```bash
mysql -u phpdb -p phpdb < data/sakila-tables.sql
mysql -u phpdb -p phpdb < data/sakila-data-notriggers.sql
```

3. Update `config/database.php` if your credentials differ.

## Installation

From the project root:

```bash
composer bench:install
```

Or from this directory:

```bash
composer install
```

## Running Benchmarks

### From Project Root

```bash
# Run all benchmarks
composer bench

# Run only PhpDb benchmarks
composer bench:phpdb

# Run only Laminas Db benchmarks
composer bench:laminas

# Run only Doctrine DBAL benchmarks
composer bench:doctrine
```

### From Benchmark Directory

```bash
cd test/benchmark

# Run all benchmarks
composer bench

# Run specific benchmark class
vendor/bin/phpbench run --filter=PhpDbBench --report=consistent

# Run with full statistics
vendor/bin/phpbench run --report=full

# Save results for later comparison
composer bench:save

# Compare saved results
composer bench:compare
```

## Benchmark Categories

The benchmarks are organized into 8 categories:

1. **SQL Generation Only** (bench1*): Pure query builder cost, no database execution
2. **Simple Operations** (bench2*): Single row CRUD by primary key
3. **Parameterized Queries** (bench3*): Prepared statements with bound parameters
4. **Filtered Queries** (bench4*): WHERE conditions without joins
5. **Joins** (bench5*): Various join complexities
6. **Complex Queries** (bench6*): Subqueries and aggregates
7. **Batch Operations** (bench7*): Multiple sequential operations
8. **Direct Connection** (bench8*): Raw SQL baseline comparison

## Configuration

- **Database**: `config/database.php`
- **PHPBench**: `phpbench.json`

## Understanding Results

- **mode**: Most frequently occurring time (more stable than mean)
- **rstdev**: Relative standard deviation (lower is better)
- **mem_peak**: Peak memory usage
- **revs**: Number of revolutions per iteration
- **its**: Number of iterations