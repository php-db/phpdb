# phpdb

The continuation of the Laminas Db component, now under the `php-db` organization.

The following information is outdated and will be updated in the coming days.

[![Build Status](https://github.com/php-db/phpdb/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/php-db/phpdb/actions/workflows/continuous-integration.yml)

`PhpDb` is a component that abstract the access to a Database using an object
oriented API to build the queries. `PhpDb` consumes different storage adapters
to access different database vendors such as MySQL, PostgreSQL, Oracle, IBM DB2,
Microsoft Sql Server, PDO, etc.

## Contributing

Please be sure to read the [contributor's guide](https://github.com/php-db/.github/blob/main/CONTRIBUTING.md) for general information on contributing.
This section outlines specifics for php-db.

### Test suites

The `phpunit.xml.dist` file defines two test suites, "unit test" and "integration test".
You can run one or the other using the `--testsuite` option to `phpunit`:

```bash
./vendor/bin/phpunit --testsuite "unit test" # unit tests only
./vendor/bin/phpunit --testsuite "integration test" # integration tests only
```

Unit tests do not require additional functionality beyond having the appropriate database extensions present and loaded in your PHP binary.

#### Integration tests

To run the integration tests, you need databases.
So, the repository includes a [Docker Compose][docker-compose] configuration which allows you to start a test environment that provides several of our target databases, including _MySQL_ and _PostgreSQL_, and SQLite.

To start up the configuration, run the following command:

```bash
docker compose up -d
```

To test that the environment is up and running, run the following command:

```bash
docker compose ps
```

You should see output similar to the following:

```bash
docker compose ps
NAME                      IMAGE                                            COMMAND                SERVICE      CREATED       STATUS       PORTS
laminas-db-mysql-1        docker.io/library/laminas-db-mysql:latest        "mysqld"               mysql        7 hours ago   Up 7 hours
laminas-db-php-1          docker.io/library/laminas-db-php:latest          "apache2-foreground"   php          7 hours ago   Up 7 hours
laminas-db-postgresql-1   docker.io/library/laminas-db-postgresql:latest   "postgres"             postgresql   7 hours ago   Up 7 hours
```

If you see three containers listed, then they're all running, and you are ready to run the test suite.
So, copy `phpunit.xml.dist` to `phpunit.xml`, and change the following environment variable to "true" to enable the three databases:

- TESTS_PHPDB_ADAPTER_DRIVER_MYSQL
- TESTS_PHPDB_ADAPTER_DRIVER_PGSQL
- TESTS_PHPDB_ADAPTER_DRIVER_SQLITE_MEMORY

From there, you can run the integration tests by running the following command:

```bash
docker compose exec php composer test-integration
```

> [!TIP]
> If you want to grow your Docker Compose knowledge, grab a (free) copy of [Deploy with Docker Compose][deploy-with-docker-compose].

-----

- File issues at <https://github.com/php-db/phpdb/issues>
- Documentation is at <https://docs.php-db.dev>

[docker-compose]: https://docs.docker.com/compose/intro/features-uses/
[deploy-with-docker-compose]: https://deploywithdockercompose.com
