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

## Development tinker

Applications can add an interactive development REPL with PsySH:

```bash
composer require --dev psy/psysh
php bin/camoo tinker
```

The `tinker` command is optional and is not installed as a production
dependency. It starts PsySH after the application's normal CLI bootstrap.

## Authentication and authorization middleware

Applications provide identity and permission logic, while the framework
enforces the request pipeline:

```php
use CAMOO\Http\Caller;
use CAMOO\Http\Middleware\AuthenticationMiddleware;
use CAMOO\Http\Middleware\AuthorizationMiddleware;
use Camoo\Http\Curl\Infrastructure\Response;

$caller = new Caller(CONFIG, [
    new AuthenticationMiddleware(
    static fn ($request) => $userRepository->fromRequest($request),
    new Response(statusCode: 401),
    ),
    new AuthorizationMiddleware(
    static fn ($request) => $policy->allows($request->getAttribute('identity'), $request),
    new Response(statusCode: 403),
    ),
]);

$response = $caller->route();
```

The authentication middleware stores the resolved identity as the `identity`
request attribute. Successful login flows should call `Session::regenerateId()`
before issuing the authenticated response.
