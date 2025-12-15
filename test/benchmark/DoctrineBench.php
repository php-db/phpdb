<?php

declare(strict_types=1);

namespace PhpDbBenchmark;

use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
class DoctrineBench
{
    private \Doctrine\DBAL\Connection $connection;

    public function setUp(): void
    {
        $config = require __DIR__ . '/config/database.php';

        $this->connection = \Doctrine\DBAL\DriverManager::getConnection([
            'dbname'   => $config['database'],
            'user'     => $config['username'],
            'password' => $config['password'],
            'host'     => $config['hostname'],
            'driver'   => 'pdo_mysql',
            'charset'  => $config['charset'] ?? 'utf8mb4',
        ]);

        // Clean up test data to prevent auto-increment overflow (actor_id is SMALLINT max 65535)
        // Only delete actors with actor_id > 200 (original Sakila has 200 actors) to avoid FK constraints
        $this->connection->executeStatement("DELETE FROM actor WHERE actor_id > 200");
        $this->connection->executeStatement("ALTER TABLE actor AUTO_INCREMENT = 201");
    }

    public function tearDown(): void
    {
        $this->connection->close();
    }

    // =========================================================================
    // 1. SQL GENERATION ONLY (no DB, pure query builder cost)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(15)]
    public function bench1a_BuildSelectSimple(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('film')
           ->where('film_id = :id')
           ->setParameter('id', 1);
        $sql = $qb->getSQL();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(15)]
    public function bench1b_BuildSelectComplex(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('film.*', 'actor.first_name', 'actor.last_name')
           ->from('film')
           ->join('film', 'film_actor', 'fa', 'film.film_id = fa.film_id')
           ->join('fa', 'actor', 'actor', 'fa.actor_id = actor.actor_id')
           ->where('film.rating = :rating')
           ->orderBy('film.title', 'ASC')
           ->setMaxResults(10)
           ->setParameter('rating', 'PG');
        $sql = $qb->getSQL();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(15)]
    public function bench1c_BuildInsert(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->insert('actor')
           ->setValue('first_name', ':first_name')
           ->setValue('last_name', ':last_name')
           ->setParameter('first_name', 'TEST')
           ->setParameter('last_name', 'ACTOR');
        $sql = $qb->getSQL();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(15)]
    public function bench1d_BuildUpdate(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('actor')
           ->set('first_name', ':first_name')
           ->where('actor_id = :id')
           ->setParameter('first_name', 'UPDATED')
           ->setParameter('id', 1);
        $sql = $qb->getSQL();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(2000)]
    #[Bench\Iterations(15)]
    public function bench1e_BuildDelete(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('actor')
           ->where('actor_id = :id')
           ->setParameter('id', 1);
        $sql = $qb->getSQL();
    }

    // =========================================================================
    // 2. SIMPLE OPERATIONS (single row by primary key)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench2a_SelectByPrimaryKey(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('film')
           ->where('film_id = :id')
           ->setParameter('id', 1);
        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench2b_InsertSingleRow(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->insert('actor')
           ->setValue('first_name', ':first_name')
           ->setValue('last_name', ':last_name')
           ->setParameter('first_name', 'TEST')
           ->setParameter('last_name', 'ACTOR');
        $qb->executeStatement();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench2c_UpdateByPrimaryKey(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('actor')
           ->set('first_name', ':first_name')
           ->where('actor_id = :id')
           ->setParameter('first_name', 'UPDATED')
           ->setParameter('id', 1);
        $qb->executeStatement();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench2d_DeleteByPrimaryKey(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('actor')
           ->where('actor_id = :id')
           ->setParameter('id', 999);
        $qb->executeStatement();
    }

    // =========================================================================
    // 3. PARAMETERIZED QUERIES (prepared statements with bound params)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench3a_SelectWithParams(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('film')
           ->where('rating = :rating')
           ->andWhere('rental_duration = :duration')
           ->andWhere('rental_rate = :rate')
           ->setParameter('rating', 'PG')
           ->setParameter('duration', 5)
           ->setParameter('rate', 0.99);
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench3b_InsertWithParams(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->insert('actor')
           ->setValue('first_name', ':first_name')
           ->setValue('last_name', ':last_name')
           ->setParameter('first_name', 'JOHN')
           ->setParameter('last_name', 'DOE');
        $qb->executeStatement();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench3c_UpdateWithParams(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('actor')
           ->set('first_name', ':first_name')
           ->set('last_name', ':last_name')
           ->where('actor_id = :id')
           ->setParameter('first_name', 'JANE')
           ->setParameter('last_name', 'SMITH')
           ->setParameter('id', 1);
        $qb->executeStatement();
    }

    // =========================================================================
    // 4. FILTERED QUERIES (WHERE conditions, no joins)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench4a_SelectWithConditions(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('film')
           ->where('rating = :rating')
           ->andWhere('rental_duration = :duration')
           ->orderBy('title', 'ASC')
           ->setMaxResults(10)
           ->setParameter('rating', 'PG')
           ->setParameter('duration', 5);
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench4b_UpdateWithConditions(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update('film')
           ->set('rental_rate', ':rate')
           ->where('rating = :rating')
           ->andWhere('rental_duration = :duration')
           ->setParameter('rate', 4.99)
           ->setParameter('rating', 'NC-17')
           ->setParameter('duration', 3);
        $qb->executeStatement();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench4c_DeleteWithConditions(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('actor')
           ->where('first_name = :first')
           ->andWhere('last_name = :last')
           ->setParameter('first', 'TEST')
           ->setParameter('last', 'ACTOR');
        $qb->executeStatement();
    }

    // =========================================================================
    // 5. JOINS (increasing complexity)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench5a_JoinTwoTables(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('film.*', 'language.name AS language_name')
           ->from('film')
           ->join('film', 'language', 'language', 'film.language_id = language.language_id')
           ->where('film.film_id = :id')
           ->setParameter('id', 1);
        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench5b_ManyToManyJoin(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('film.*', 'actor.first_name', 'actor.last_name')
           ->from('film')
           ->join('film', 'film_actor', 'fa', 'film.film_id = fa.film_id')
           ->join('fa', 'actor', 'actor', 'fa.actor_id = actor.actor_id')
           ->where('film.film_id = :id')
           ->setParameter('id', 1);
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench5c_JoinWithAggregate(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('film.film_id', 'film.title', 'COUNT(fa.actor_id) AS actor_count')
           ->from('film')
           ->join('film', 'film_actor', 'fa', 'film.film_id = fa.film_id')
           ->groupBy('film.film_id', 'film.title')
           ->setMaxResults(10);
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    // =========================================================================
    // 6. COMPLEX QUERIES
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench6a_Subquery(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select(
               'customer.customer_id',
               'customer.first_name',
               'customer.last_name',
               '(SELECT COUNT(*) FROM rental WHERE rental.customer_id = customer.customer_id) AS rental_count'
           )
           ->from('customer')
           ->setMaxResults(10);
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench6b_AggregateGroupBy(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('rating', 'COUNT(*) AS count', 'AVG(length) AS avg_length')
           ->from('film')
           ->groupBy('rating');
        $result = $qb->executeQuery();
        while ($row = $result->fetchAssociative()) {}
    }

    // =========================================================================
    // 7. BATCH OPERATIONS
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(15)]
    public function bench7a_InsertBatch(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $qb = $this->connection->createQueryBuilder();
            $qb->insert('actor')
               ->setValue('first_name', ':first_name')
               ->setValue('last_name', ':last_name')
               ->setParameter('first_name', 'BATCH' . $i)
               ->setParameter('last_name', 'TEST');
            $qb->executeStatement();
        }
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(15)]
    public function bench7b_UpdateBatch(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update('actor')
               ->set('first_name', ':first_name')
               ->where('actor_id = :id')
               ->setParameter('first_name', 'UPDATED' . $i)
               ->setParameter('id', $i);
            $qb->executeStatement();
        }
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(150)]
    #[Bench\Iterations(15)]
    public function bench7c_DeleteBatch(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('actor')
           ->where('first_name = :first_name')
           ->setParameter('first_name', 'BATCH0');
        $qb->executeStatement();
    }

    // =========================================================================
    // 8. DIRECT CONNECTION (raw SQL, no query builder)
    // =========================================================================

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8a_DirectSelectByPrimaryKey(): void
    {
        $result = $this->connection->executeQuery('SELECT * FROM film WHERE film_id = ?', [1]);
        $row = $result->fetchAssociative();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8b_DirectInsertSingleRow(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO actor (first_name, last_name) VALUES (?, ?)',
            ['TEST', 'ACTOR']
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8c_DirectUpdateByPrimaryKey(): void
    {
        $this->connection->executeStatement(
            'UPDATE actor SET first_name = ? WHERE actor_id = ?',
            ['UPDATED', 1]
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8d_DirectDeleteByPrimaryKey(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM actor WHERE actor_id = ?',
            [999]
        );
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8e_DirectSelectWithConditions(): void
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM film WHERE rating = ? AND rental_duration = ? ORDER BY title ASC LIMIT 10',
            ['PG', 5]
        );
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8f_DirectJoinTwoTables(): void
    {
        $result = $this->connection->executeQuery(
            'SELECT film.*, language.name AS language_name FROM film JOIN language ON film.language_id = language.language_id WHERE film.film_id = ?',
            [1]
        );
        $row = $result->fetchAssociative();
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8g_DirectManyToManyJoin(): void
    {
        $result = $this->connection->executeQuery(
            'SELECT film.*, actor.first_name, actor.last_name FROM film JOIN film_actor fa ON film.film_id = fa.film_id JOIN actor ON fa.actor_id = actor.actor_id WHERE film.film_id = ?',
            [1]
        );
        while ($row = $result->fetchAssociative()) {}
    }

    #[Bench\Warmup(10)]
    #[Bench\Revs(300)]
    #[Bench\Iterations(15)]
    public function bench8h_DirectAggregateGroupBy(): void
    {
        $result = $this->connection->executeQuery(
            'SELECT rating, COUNT(*) AS count, AVG(length) AS avg_length FROM film GROUP BY rating'
        );
        while ($row = $result->fetchAssociative()) {}
    }
}
