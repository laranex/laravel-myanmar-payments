# Contribution Guide

Thank you for considering contributing to Laravel Myanmar Payments! Please review the following guidelines before submitting a pull request.

For significant changes, please open an issue first so we can discuss the approach.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit, and push
4. Open a pull request detailing your changes

## Guidelines

- Ensure the coding style passes by running `composer lint`.
- Send a coherent commit history, making sure each commit in your pull request is meaningful.
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
- Please remember that we follow [SemVer](http://semver.org/).

## Setup

This package depends on [`laranex/php-myanmar-payments`](https://github.com/laranex/php-myanmar-payments), installed from Packagist like any other dependency:

```bash
git clone git@github.com:<you>/laravel-myanmar-payments.git
cd laravel-myanmar-payments
```

To change both packages together, clone `php-myanmar-payments` next to this one and point Composer at it for the session (don't commit the change):

```bash
composer config repositories.core '{"type": "path", "url": "../php-myanmar-payments", "options": {"versions": {"laranex/php-myanmar-payments": "4.x-dev"}}}'
```

Then install the dev dependencies:

```bash
composer install
```

## Lint

Lint your code:

```bash
composer lint
```

## Tests

Run all tests:

```bash
composer test
```
