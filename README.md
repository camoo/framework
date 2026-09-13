# CAMOO Framework

Official CAMOO Framework library supporting **PHP 8.4+**.

## Requirement

- PHP 8.4 or higher
- Composer

## Docker Setup

You can run Composer and PHPUnit tests inside a PHP 8.4 Docker container using Docker or Docker Compose.

### Using Docker Compose

```bash
# Install dependencies
docker compose run --rm app composer install

# Update dependencies
docker compose run --rm app composer update

# Run PHPUnit tests
docker compose run --rm app vendor/bin/phpunit
```

### Using Docker CLI directly

```bash
# Build the PHP 8.4 image
docker build -t camoo-framework-php84 .

# Run test suite
docker run --rm -v "$(pwd)":/app -w /app camoo-framework-php84 vendor/bin/phpunit
```
