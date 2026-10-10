[![CI Orchestrator](https://github.com/xver-por-ver/micartera-domain/actions/workflows/ci-orchestrator.yml/badge.svg?branch=main&event=push)](https://github.com/xver-por-ver/micartera-domain/actions/workflows/ci-orchestrator.yml)

## Continuous Integration

This project uses GitHub Actions for continuous integration.

The main workflow is `.github/workflows/ci-orchestrator.yml`, which delegates to reusable workflows in this order:

1. `.github/workflows/symfony-recipe-check.yml` checks whether Symfony Flex recipes are outdated.
2. `.github/workflows/application-quality-assurance.yml` runs the application quality checks after the recipe check succeeds.

CI runs on pushes and pull requests targeting `main`, except for documentation-only changes, license updates, and changes under `translations/`.

The application quality workflow performs:

- Composer validation
- Dependency installation
- PHPUnit test suite execution with coverage output
- Psalm static analysis

## Upgrading from 2.0.2 or earlier

Before running `doctrine:migrations:migrate`, update the Doctrine migrations version table to reflect the renamed initial migration. The initial schema migration was renamed from `VersionInitial` to `Version20231217111100`; without this step, Doctrine may try to run the initial schema migration again.

Run these commands once against each existing database, using the same environment and database configuration as the subsequent migration:

```sh
bin/console doctrine:migrations:version 'DoctrineMigrations\VersionInitial' --delete
bin/console doctrine:migrations:version 'DoctrineMigrations\Version20231217111100' --add
```

Then run the pending migrations as usual:

```sh
bin/console doctrine:migrations:migrate
```

Do not run the version-table commands on a fresh database, or on a database that has already recorded `DoctrineMigrations\Version20231217111100` as executed. Back up the database before upgrading.

## Symfony Recipe Maintenance

`.github/workflows/symfony-recipe-maintenance.yml` keeps Symfony Flex recipes current. It runs on pushes and pull requests targeting `main`, on a weekly schedule, and by manual dispatch.

When recipe updates are available, it applies `composer recipes:update`, commits the changes, and opens or updates the `automated/symfony-recipe-updates` pull request. If a recipe update fails or creates conflicts, the workflow fails so the update can be resolved manually.
