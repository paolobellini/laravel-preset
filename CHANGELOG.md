# Changelog

All notable changes to `paolobellini/laravel-preset` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-28

### Added

- `preset:install` artisan command with four selectable groups
  (`--configs`, `--ai`, `--scripts`, `--github`), interactive multiselect, and a
  `--force` flag to overwrite existing files and dependencies.
- **configs** — copies `pint.json`, `phpstan.neon`, `rector.php` and
  `config/essentials.php` (nunomaduro/essentials with `Unguard => true`).
- **ai** — copies the `.ai/` conventions (guidelines + mcp).
- **scripts** — merges composer `require`, `require-dev` and quality scripts
  (`lint`, `analyse`, `refactor`, `type`, `coverage`, `tests`, `check:lint`,
  `check:refactor`, `php-checks`, `node-checks`, `ide-helper`, `cleanup`) without
  clobbering existing keys. Adds `barryvdh/laravel-ide-helper` and
  `fruitcake/laravel-debugbar`; skips `nunomaduro/collision` and
  `pestphp/pest-plugin-laravel` (already shipped by the starter kit). Leaves npm
  deps and scripts untouched.
- **github** — splits CI into `analyse.yml` and `tests.yml` calling the
  `paolobellini/bellini.one` reusable workflows (`laravel-lint@v1.0`,
  `laravel-test@v1.0`), plus `security.yml` running the Trivy security action on
  PRs targeting `staging`. Removes the superseded starter-kit `lint.yml` and
  `tests.yml`.
- Automatic `composer update` after install when the `scripts` group is selected,
  with **Laravel Sail detection** — uses `./vendor/bin/sail composer update` only
  when Sail is both installed (`vendor/bin/sail`) and configured (`compose.yaml`
  or `docker-compose.yml`), otherwise plain `composer update`. Skippable with
  `--no-install`.

[1.0.0]: https://github.com/paolobellini/laravel-preset/releases/tag/v1.0.0
