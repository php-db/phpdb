# Docker Deployment

This guide covers Docker deployment for phpdb applications, applicable to both Laminas MVC and Mezzio frameworks.

## Web Server Options

Two web server options are supported: **Nginx with PHP-FPM** (recommended for production) and **Apache** (simpler for development).

### Nginx with PHP-FPM

Create a `Dockerfile` in your project root:

```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache git zip unzip \
    && docker-php-ext-install pdo_mysql

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

Create `docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Apache

Create a `Dockerfile` in your project root:

```dockerfile
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y git zlib1g-dev libzip-dev \
    && docker-php-ext-install zip pdo_mysql \
    && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

## Database Containers

### MySQL

```yaml
mysql:
  image: mysql:8.0
  ports:
    - "3306:3306"
  command: --default-authentication-plugin=mysql_native_password
  volumes:
    - mysql_data:/var/lib/mysql
    - ./docker/mysql/init:/docker-entrypoint-initdb.d
  environment:
    - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
    - MYSQL_DATABASE=${DB_DATABASE}
    - MYSQL_USER=${DB_USERNAME}
    - MYSQL_PASSWORD=${DB_PASSWORD}
```

### PostgreSQL

```yaml
postgres:
  image: postgres:15-alpine
  ports:
    - "5432:5432"
  volumes:
    - postgres_data:/var/lib/postgresql/data
    - ./docker/postgres/init:/docker-entrypoint-initdb.d
  environment:
    - POSTGRES_DB=${DB_DATABASE}
    - POSTGRES_USER=${DB_USERNAME}
    - POSTGRES_PASSWORD=${DB_PASSWORD}
```

For PostgreSQL, add to your Dockerfile:

```dockerfile
RUN docker-php-ext-install pdo_pgsql
```

### phpMyAdmin (Optional)

```yaml
phpmyadmin:
  image: phpmyadmin/phpmyadmin
  ports:
    - "8081:80"
  depends_on:
    - mysql
  environment:
    - PMA_HOST=mysql
    - PMA_PORT=3306
```

## Complete Examples

### Nginx + MySQL

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www
    depends_on:
      - mysql
    environment:
      - DB_TYPE=mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=mysql
      - DB_PORT=3306

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    depends_on:
      - mysql
    environment:
      - PMA_HOST=mysql
      - PMA_PORT=3306

volumes:
  mysql_data:
```

### Apache + MySQL

```yaml
version: "3.8"

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
    depends_on:
      - mysql
    environment:
      - DB_TYPE=mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_HOSTNAME=mysql
      - DB_PORT=3306

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    depends_on:
      - mysql
    environment:
      - PMA_HOST=mysql
      - PMA_PORT=3306

volumes:
  mysql_data:
```

## Environment Variables

Create a `.env` file in your project root:

```env
DB_DATABASE=myapp
DB_USERNAME=appuser
DB_PASSWORD=apppassword
MYSQL_ROOT_PASSWORD=rootpassword
```

## Database Initialization

Place SQL files in `./docker/mysql/init/` (or `./docker/postgres/init/` for PostgreSQL). Files execute in alphanumeric order on first container start.

Example `docker/mysql/init/01-schema.sql`:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_status ON users(status);
```

Example `docker/mysql/init/02-seed.sql`:

```sql
INSERT INTO users (username, email, status) VALUES
    ('alice', 'alice@example.com', 'active'),
    ('bob', 'bob@example.com', 'active'),
    ('charlie', 'charlie@example.com', 'inactive');
```

## Running the Application

```bash
# Start all services
docker compose up -d

# Check status
docker compose ps

# View logs
docker compose logs -f app

# Stop services
docker compose down
```

Access your application at `http://localhost:8080` and phpMyAdmin at `http://localhost:8081`.