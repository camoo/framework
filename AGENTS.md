# CAMOO Framework contributor guide

## Project overview

CAMOO Framework is a reusable PHP library. Runtime code lives in `src/` and is
autoloaded with the `CAMOO\` PSR-4 namespace. Tests live in `tests/TestCase/`
under the `CAMOO\Test\` namespace. The repository also contains framework
bootstrap/configuration helpers in `config/` and `include/`.

Before changing code, read the relevant class, its neighboring abstractions,
and its tests. Preserve the existing public API and compatibility with the
dependency version ranges declared in `composer.json` (notably CakePHP,
Symfony, Doctrine, Twig, and PHPUnit).

## Requirements and commands

- PHP 8.4 or newer
- Composer
- `ext-mbstring` and `ext-json` for the library; the Docker image also installs
  extensions used by the test environment

Preferred commands from the repository root:

```bash
# Install the locked dependency set
composer install

# Run the complete test suite
composer test

# Equivalent direct test command
vendor/bin/phpunit
```

When PHP/Composer are not installed locally, use the documented PHP 8.4
container workflow:

```bash
docker compose build
docker compose run --rm app composer install
docker compose run --rm app vendor/bin/phpunit
```

`phpunit.xml.dist` bootstraps `tests/bootstrap.php`, discovers tests below
`tests/TestCase`, and includes `src/` for coverage. Run a focused test file
while iterating, for example:

```bash
vendor/bin/phpunit tests/TestCase/Http/ServerRequestTest.php
```

There is no configured lint or static-analysis script in `composer.json`; do
not imply that such a check passed unless you ran an explicitly available
tool. At minimum, run the relevant PHPUnit tests and inspect the final diff.

## Making changes

- Put production classes in the matching `src/<Area>/` directory and use the
  corresponding `CAMOO\<Area>` namespace.
- Put tests in the matching `tests/TestCase/<Area>/` directory and name them
  `*Test.php`; extend `PHPUnit\Framework\TestCase` and follow the existing
  PHPUnit attribute/assertion style.
- Add or update tests for behavior changes, including success paths, invalid
  input, exceptions, and boundary/null cases where applicable.
- Follow the local PHP style: typed properties and parameters, explicit return
  types, `declare(strict_types=1)` in production files, short array syntax, and
  PSR-4 class/file names. Keep comments focused on non-obvious behavior.
- Prefer small, localized changes. Avoid unrelated formatting, renames, or
  framework-wide refactors in a bug fix.
- Treat request data, cookies, sessions, templates, mail, and error rendering
  as security-sensitive surfaces. Preserve existing sanitization, validation,
  exception, and escaping behavior unless the change explicitly addresses it.
- If dependencies change, update `composer.lock` consistently and verify the
  suite against the resulting dependency set. Do not commit `vendor/`.

## Test and review expectations

Tests rely on the definitions and temporary paths established in
`tests/bootstrap.php`; use that bootstrap rather than inventing a second test
environment. Tests may write cache/log data under the system temporary
directory.

Before handing off a change:

1. Run the narrowest relevant PHPUnit test(s).
2. Run the full suite when practical, especially for changes to shared
   utilities, dependency wiring, HTTP, errors, templates, or configuration.
3. Check `git diff --check` and review `git diff` for accidental changes,
   generated files, credentials, or debug output.
4. Report any checks that could not run, such as missing local PHP/Composer or
   unavailable Docker, instead of treating them as successful.

## Git and generated files

Keep changes reviewable and do not discard existing user work. Common local or
generated paths already ignored by the repository include `vendor/`,
`phpunit.xml`, `.phpunit.result.cache`, coverage output, `.env`, and IDE files.
Commit source, tests, configuration, and documentation that are intentionally
part of the change; leave machine-specific artifacts untracked.
