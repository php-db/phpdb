<?php

declare(strict_types=1);

namespace PhpDbBenchmark;

use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
class PhpDbBench
{
    private \PhpDb\Adapter\Adapter $adapter;
    private \PhpDb\Sql\Sql $sql;
    private \PhpDb\Adapter\Platform\PlatformInterface $platform;

    public function setUp(): void
    {
        $config = require __DIR__ . '/config/database.php';

        $connection = new \PhpDb\Adapter\Mysql\Driver\Pdo\Connection([
            'hostname' => $config['hostname'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset'  => $config['charset'] ?? 'utf8mb4',
        ]);
        $driver = new \PhpDb\Adapter\Mysql\Driver\Pdo\Pdo(
            $connection,
            new \PhpDb\Adapter\Driver\Pdo\Statement(),
            new \PhpDb\Adapter\Driver\Pdo\Result()
        );
        $this->platform = new \PhpDb\Adapter\Mysql\Platform\Mysql($driver);
        $this->adapter = new \PhpDb\Adapter\Adapter(
            $driver,
            $this->platform,
            new \PhpDb\ResultSet\ResultSet()
        );
        $this->sql = new \PhpDb\Sql\Sql($this->adapter);

        // Clean up test data to prevent auto-increment overflow (actor_id is SMALLINT max 65535)
        // Only delete actors with actor_id > 200 (original Sakila has 200 actors) to avoid FK constraints
        $this->adapter->query("DELETE FROM actor WHERE actor_id > 200", \PhpDb\Adapter\Adapter::QUERY_MODE_EXECUTE);
        $this->adapter->query("ALTER TABLE actor AUTO_INCREMENT = 201", \PhpDb\Adapter\Adapter::QUERY_MODE_EXECUTE);
    }

    public function tearDown(): void
    {
        $this->adapter->getDriver()->getConnection()->disconnect();
    }

    // =========================================================================
    // 1. SQL GENERATION ONLY (no DB, pure query builder cost)
    // =========================================================================

    #[Bench\Warmup(2)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(5)]
    public function bench1a_BuildSelectSimple(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->where(['film_id' => 1]);
        $sql = $select->getSqlString($this->platform);
    }

    #[Bench\Warmup(2)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(5)]
    public function bench1b_BuildSelectComplex(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->join('film_actor', 'film.film_id = film_actor.film_id', []);
        $select->join('actor', 'film_actor.actor_id = actor.actor_id', ['first_name', 'last_name']);
        $select->where(['film.rating' => 'PG']);
        $select->order('film.title ASC');
        $select->limit('10');
        $sql = $select->getSqlString($this->platform);
    }

    #[Bench\Warmup(2)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(5)]
    public function bench1c_BuildInsert(): void
    {
        $insert = new \PhpDb\Sql\Insert('actor');
        $insert->values(['first_name' => 'TEST', 'last_name' => 'ACTOR']);
        $sql = $insert->getSqlString($this->platform);
    }

    #[Bench\Warmup(2)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(5)]
    public function bench1d_BuildUpdate(): void
    {
        $update = new \PhpDb\Sql\Update('actor');
        $update->set(['first_name' => 'UPDATED']);
        $update->where(['actor_id' => 1]);
        $sql = $update->getSqlString($this->platform);
    }

    #[Bench\Warmup(2)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(5)]
    public function bench1e_BuildDelete(): void
    {
        $delete = new \PhpDb\Sql\Delete('actor');
        $delete->where(['actor_id' => 1]);
        $sql = $delete->getSqlString($this->platform);
    }

    // =========================================================================
    // 2. SIMPLE OPERATIONS (single row by primary key)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench2a_SelectByPrimaryKey(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->where(['film_id' => 1]);
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench2b_InsertSingleRow(): void
    {
        $insert = new \PhpDb\Sql\Insert('actor');
        $insert->values(['first_name' => 'TEST', 'last_name' => 'ACTOR']);
        $stmt = $this->sql->prepareStatementForSqlObject($insert);
        $stmt->execute();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench2c_UpdateByPrimaryKey(): void
    {
        $update = new \PhpDb\Sql\Update('actor');
        $update->set(['first_name' => 'UPDATED']);
        $update->where(['actor_id' => 1]);
        $stmt = $this->sql->prepareStatementForSqlObject($update);
        $stmt->execute();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench2d_DeleteByPrimaryKey(): void
    {
        $delete = new \PhpDb\Sql\Delete('actor');
        $delete->where(['actor_id' => 999]);
        $stmt = $this->sql->prepareStatementForSqlObject($delete);
        $stmt->execute();
    }

    // =========================================================================
    // 3. PARAMETERIZED QUERIES (prepared statements with bound params)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench3a_SelectWithParams(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->where(['rating' => 'PG', 'rental_duration' => 5, 'rental_rate' => 0.99]);
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench3b_InsertWithParams(): void
    {
        $insert = new \PhpDb\Sql\Insert('actor');
        $insert->values([
            'first_name' => 'JOHN',
            'last_name' => 'DOE',
        ]);
        $stmt = $this->sql->prepareStatementForSqlObject($insert);
        $stmt->execute();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench3c_UpdateWithParams(): void
    {
        $update = new \PhpDb\Sql\Update('actor');
        $update->set(['first_name' => 'JANE', 'last_name' => 'SMITH']);
        $update->where(['actor_id' => 1]);
        $stmt = $this->sql->prepareStatementForSqlObject($update);
        $stmt->execute();
    }

    // =========================================================================
    // 4. FILTERED QUERIES (WHERE conditions, no joins)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench4a_SelectWithConditions(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->where(['rating' => 'PG', 'rental_duration' => 5]);
        $select->order('title ASC');
        $select->limit(10);
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench4b_UpdateWithConditions(): void
    {
        $update = new \PhpDb\Sql\Update('film');
        $update->set(['rental_rate' => 4.99]);
        $update->where(['rating' => 'NC-17', 'rental_duration' => 3]);
        $stmt = $this->sql->prepareStatementForSqlObject($update);
        $stmt->execute();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench4c_DeleteWithConditions(): void
    {
        $delete = new \PhpDb\Sql\Delete('actor');
        $delete->where(['first_name' => 'TEST', 'last_name' => 'ACTOR']);
        $stmt = $this->sql->prepareStatementForSqlObject($delete);
        $stmt->execute();
    }

    // =========================================================================
    // 5. JOINS (increasing complexity)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench5a_JoinTwoTables(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->join('language', 'film.language_id = language.language_id', ['language_name' => 'name']);
        $select->where(['film.film_id' => 1]);
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench5b_ManyToManyJoin(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->join('film_actor', 'film.film_id = film_actor.film_id', []);
        $select->join('actor', 'film_actor.actor_id = actor.actor_id', ['first_name', 'last_name']);
        $select->where(['film.film_id' => 1]);
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench5c_JoinWithAggregate(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->columns(['film_id', 'title', 'actor_count' => new \PhpDb\Sql\Expression('COUNT(film_actor.actor_id)')]);
        $select->join('film_actor', 'film.film_id = film_actor.film_id', []);
        $select->group(['film.film_id', 'film.title']);
        $select->limit('10');
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    // =========================================================================
    // 6. COMPLEX QUERIES
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench6a_Subquery(): void
    {
        $subSelect = new \PhpDb\Sql\Select('rental');
        $subSelect->columns(['rental_count' => new \PhpDb\Sql\Expression('COUNT(*)')]);
        $subSelect->where('rental.customer_id = customer.customer_id');

        $select = new \PhpDb\Sql\Select('customer');
        $select->columns(['customer_id', 'first_name', 'last_name', 'rental_count' => $subSelect]);
        $select->limit('10');
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench6b_AggregateGroupBy(): void
    {
        $select = new \PhpDb\Sql\Select('film');
        $select->columns(['rating', 'count' => new \PhpDb\Sql\Expression('COUNT(*)'), 'avg_length' => new \PhpDb\Sql\Expression('AVG(length)')]);
        $select->group('rating');
        $stmt = $this->sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        foreach ($result as $row) {}
    }

    // =========================================================================
    // 7. BATCH OPERATIONS
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(5)]
    public function bench7a_InsertBatch(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $insert = new \PhpDb\Sql\Insert('actor');
            $insert->values(['first_name' => 'BATCH' . $i, 'last_name' => 'TEST']);
            $stmt = $this->sql->prepareStatementForSqlObject($insert);
            $stmt->execute();
        }
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(5)]
    public function bench7b_UpdateBatch(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $update = new \PhpDb\Sql\Update('actor');
            $update->set(['first_name' => 'UPDATED' . $i]);
            $update->where(['actor_id' => $i]);
            $stmt = $this->sql->prepareStatementForSqlObject($update);
            $stmt->execute();
        }
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(5)]
    public function bench7c_DeleteBatch(): void
    {
        $delete = new \PhpDb\Sql\Delete('actor');
        $delete->where(['first_name' => 'BATCH0']);
        $stmt = $this->sql->prepareStatementForSqlObject($delete);
        $stmt->execute();
    }

    // =========================================================================
    // 8. DIRECT CONNECTION (raw SQL, no query builder)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8a_DirectSelectByPrimaryKey(): void
    {
        $result = $this->adapter->query('SELECT * FROM film WHERE film_id = ?', [1]);
        $row = $result->current();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8b_DirectInsertSingleRow(): void
    {
        $this->adapter->query(
            'INSERT INTO actor (first_name, last_name) VALUES (?, ?)',
            ['TEST', 'ACTOR']
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8c_DirectUpdateByPrimaryKey(): void
    {
        $this->adapter->query(
            'UPDATE actor SET first_name = ? WHERE actor_id = ?',
            ['UPDATED', 1]
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8d_DirectDeleteByPrimaryKey(): void
    {
        $this->adapter->query(
            'DELETE FROM actor WHERE actor_id = ?',
            [999]
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8e_DirectSelectWithConditions(): void
    {
        $result = $this->adapter->query(
            'SELECT * FROM film WHERE rating = ? AND rental_duration = ? ORDER BY title ASC LIMIT 10',
            ['PG', 5]
        );
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8f_DirectJoinTwoTables(): void
    {
        $result = $this->adapter->query(
            'SELECT film.*, language.name AS language_name FROM film JOIN language ON film.language_id = language.language_id WHERE film.film_id = ?',
            [1]
        );
        $row = $result->current();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8g_DirectManyToManyJoin(): void
    {
        $result = $this->adapter->query(
            'SELECT film.*, actor.first_name, actor.last_name FROM film JOIN film_actor fa ON film.film_id = fa.film_id JOIN actor ON fa.actor_id = actor.actor_id WHERE film.film_id = ?',
            [1]
        );
        foreach ($result as $row) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(5)]
    public function bench8h_DirectAggregateGroupBy(): void
    {
        $result = $this->adapter->query(
            'SELECT rating, COUNT(*) AS count, AVG(length) AS avg_length FROM film GROUP BY rating',
            \PhpDb\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        foreach ($result as $row) {}
    }
}