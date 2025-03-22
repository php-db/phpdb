# laminas-db

[![Build Status](https://github.com/laminas/laminas-db/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas/laminas-db/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

`Laminas\Db` is a component that abstract the access to a Database using an object
oriented API to build the queries. `Laminas\Db` consumes different storage adapters
to access different database vendors such as MySQL, PostgreSQL, Oracle, IBM DB2,
Microsoft Sql Server, PDO, etc.

## Contributing

Please be sure to read the [contributor's guide](https://github.com/laminas/.github/blob/main/CONTRIBUTING.md) for general information on contributing.
This section outlines specifics for laminas-db.

### Test suites

The `phpunit.xml.dist` file defines two test suites, "unit test" and "integration test".
You can run one or the other using the `--testsuite` option to `phpunit`:

```bash
$ ./vendor/bin/phpunit --testsuite "unit test" # unit tests only
$ ./vendor/bin/phpunit --testsuite "integration test" # integration tests only
```

Unit tests do not require additional functionality beyond having the appropriate database extensions present and loaded in your PHP binary.

#### Integration tests

To run the integration tests, you need databases.
So, the repository includes a [Docker Compose][docker-compose] configuration which allows you to start a test environment that provides several of our target databases, including _MySQL_ and _PostgreSQL_, and SQLite.

To start up the configuration, run the following command:

```bash
$ docker compose up -d
```

To test that the environment is up and running, run the following command:

```bash
$ docker compose ps
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

- TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL
- TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL
- TESTS_LAMINAS_DB_ADAPTER_DRIVER_SQLITE_MEMORY

From there, you can run the integration tests by running the following command:

```bash
$ docker compose exec php composer test-integration
```

> [!TIP]
> If you want to grow your Docker Compose knowledge, grab a (free) copy of [Deploy with Docker Compose][deploy-with-docker-compose].

-----

- File issues at https://github.com/laminas/laminas-db/issues
- Documentation is at https://docs.laminas.dev/laminas-db/

[docker-compose]: https://docs.docker.com/compose/intro/features-uses/
[deploy-with-docker-compose]: https://deploywithdockercompose.com