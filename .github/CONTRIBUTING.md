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

This package depends on [`laranex/php-myanmar-payments`](https://github.com/laranex/php-myanmar-payments), which `composer.json` resolves through a path repository at `../php-myanmar-payments`. Clone both repositories side by side:

```bash
git clone git@github.com:laranex/php-myanmar-payments.git
git clone git@github.com:<you>/laravel-myanmar-payments.git
cd laravel-myanmar-payments
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

## Continuous integration

`laranex/php-myanmar-payments` is a private repository, so the `tests` workflow clones it into `../php-myanmar-payments` (branch `dev`, falling back to `main`) before installing dependencies. The workflow needs a repository (or organization) secret:

| Secret | Value |
| --- | --- |
| `LARANEX_PACKAGES_TOKEN` | A fine-grained personal access token (or GitHub App token) with **Contents: Read-only** access to `laranex/php-myanmar-payments`. |

Without the secret the clone step fails with an explanatory error. Pull requests from forks do not receive repository secrets, so their CI runs fail at that step; a maintainer re-runs them from a branch in this repository.
