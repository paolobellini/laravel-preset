# laravel-preset

Opinionated personal Laravel preset. One command scaffolds the dev tooling,
code conventions and CI used across new projects (on top of the Laravel + Vue
starter kit, so anything the starter kit already ships is not duplicated).

## Install

```bash
composer require paolobellini/laravel-preset --dev
php artisan preset:install
```

When the `scripts` group is selected, `preset:install` adds the dev
dependencies with `composer require` (prefixed with `./vendor/bin/sail` when
Laravel Sail is installed). No version constraint is ever passed, so composer
resolves the newest stable release compatible with the project — the preset
never carries a stale version around. Pass `--no-install` to write the resolved
constraints into `composer.json` without installing.

## What it does

`php artisan preset:install` is interactive — pick any of the five groups:

### `configs` — lint / format / static analysis

Copies the configs not already in the starter kit:

| File | Tool |
|------|------|
| `pint.json` | Laravel Pint (strict types, final classes, phpdoc-only types) |
| `phpstan.neon` | Larastan level 7 + the pest-plugin-phpstan and phpstan-safe-rule extensions |
| `rector.php` | Rector + rector-laravel sets, scoped to `app/` and `database/` |
| `rector-tests.php` | Rector for `tests/` — `LARAVEL_TESTING` + `PestSetList::CODING_STYLE` |
| `psalm.xml` | Psalm, scoped to taint analysis only (`errorLevel="8"`) |
| `config/essentials.php` | nunomaduro/essentials — custom overrides (`Unguard => true`, inverse of the package default) |
| `tests/Pest.php` | created when missing (`extend(TestCase)` + `RefreshDatabase`), otherwise **patched** with `pest()->tia()->locally()` |

### `ai` — conventions

Copies the `.ai/` directory only:

- `.ai/guidelines/personal/*` — precedence, comments, commits, controllers
  (action pattern), actions, caching, enums, exceptions, form-requests,
  frontend, models, pest-agent, php (Safe functions), policies, query-builder,
  resources, testing, traits, translations, typescript, workflow.
- `.ai/mcp/mcp.json`.

### `scripts` — composer quality scripts + dev deps

Merges the scripts into `composer.json` without clobbering existing keys, then
installs the dependencies with `composer require` — always unconstrained, so
every install picks up the current stable release.

Added to `require-dev` (anything already required is left untouched, use
`--force` to re-require it at the latest version): `fruitcake/laravel-debugbar`,
`larastan/larastan`, `laravel/pint`, `laravel/boost`, `laravel/pail`,
`rector/rector`, `driftingly/rector-laravel`, `pestphp/pest` and the
`pest-plugin-{type-coverage,mutate,rector,phpstan,evals,agent,faker}` plugins,
`thecodingmachine/phpstan-safe-rule`, `spatie/laravel-typescript-transformer`,
`vimeo/psalm`.
`nunomaduro/essentials`, `spatie/laravel-data`, `spatie/laravel-query-builder`
and `thecodingmachine/safe` go into `require`. `nunomaduro/collision` and
`pestphp/pest-plugin-laravel` are **not** added — they already ship with the
starter kit.

`config.allow-plugins` gets `pestphp/pest-plugin` so the pest plugins can boot.

Composer scripts are layered so a tool is declared once:

- **leaf** — one tool each: `pint`, `pint:dry`, `rector`, `rector:dry`,
  `rector:test`, `rector:test:dry`, `stan`, `taint`, `test`,
  `test:type-coverage`, `test:coverage`, `test:mutate`, `update-shards`.
- **groups** — `analyse:static` (pint + stan + rector on `app/`),
  `analyse` (`analyse:static` + `taint`), `tests` (type-coverage + coverage),
  `ci:node` (npm lint/format/types).
- **entry points** — `ci` (`ci:php` + `ci:node`), `ci:php` (`analyse` +
  `tests`), `pre-push` (`rector:test:dry` + `test:mutate`), `pre-commit`
  (`analyse:static` + `test` + `ci:node`, the by-hand equivalent of the hook).

Entry points compose groups and never re-list a leaf, so adding a tool means
editing one group.

**CI and `pre-push` are deliberately disjoint.** CI owns the application
contract — pint, stan, taint, rector over `app/`, coverage, type-coverage — so
it is asynchronous and cannot be bypassed. `pre-push` owns test-suite quality:
the mutation score and rector over `tests/`, the two slowest checks and the two
whose audience is the author. Nothing runs twice.

Every script that boots the application (`test:*`) is prefixed with
`@php artisan config:clear --ansi @no_additional_args` — a cached
`bootstrap/cache/config.php` silently overrides `config/` and `.env`, and
`@no_additional_args` stops Composer from appending your own options to
`config:clear`. The lint/analysis scripts do not boot the app and skip it.

- `composer taint` → `psalm --taint-analysis --no-cache`. Psalm is installed
  **only** for this: PHPStan/Larastan stays the static analyser, and `psalm.xml`
  runs at the most permissive error level so the report is about tainted input
  reaching a sink, not about typing.
- `composer test:mutate` → `pest --parallel --mutate --covered-only --min=65`,
  with `Composer\Config::disableProcessTimeout` so a long mutation run is not
  killed at Composer's 300s limit.

npm deps and scripts are **not** touched — the starter kit already provides
ESLint, Prettier, TypeScript and their `lint`/`format`/`types:check` scripts.

### `lefthook` — pre-commit hooks (opt-in)

Copies `lefthook.yml`, with two hooks and one job per tool — granular on
purpose, since lefthook only parallelises what it sees as separate jobs and a
failure in one still reports the others.

- **`pre-commit`** is scoped to `{staged_files}` with a `glob` per job, so a
  commit touching one `.vue` never starts a PHP job. Pint, Prettier and ESLint
  *fix* and re-stage (`stage_fixed`) instead of failing, so a commit is never
  blocked by formatting; PHPStan analyses the staged files and Pest runs with
  the coverage gate (cheap locally, since TIA replays everything untouched).
- **`pre-push`** runs only what CI does not: `rector:test:dry` and
  `test:mutate`.

`tests/Pest.php` is the one file the preset *patches* rather than copies: it
holds project-specific setup, so an existing one only gets
`pest()->tia()->locally()` appended (and nothing at all if it already configures
TIA). That line is what makes the commit hook affordable — locally a run
replays whatever the change did not touch, while CI still measures the full
suite.

Passing paths to PHPStan and Rector narrows them to those files — an error
caused elsewhere will not show up until CI. That is the trade that keeps the
commit hook fast.

The commands are prefixed with `vendor/bin/sail` only when Sail is detected in
the project; otherwise the prefix is stripped from the copied file.

**Opt-in** — unlike the other groups it is never selected by default: pass
`--lefthook`, or tick it in the interactive prompt.

Lefthook is not a composer package: install the binary once per machine
(`brew install lefthook`, or `npm i -D lefthook`), then run `lefthook install`
in the project to wire up `.git/hooks`.

### `github` — CI workflows

First removes the starter-kit `lint.yml` + `tests.yml` (superseded), then copies
the workflows. Each job runs the composer scripts from the `scripts` group
directly, so the commands and their definitions stay in this repo and cannot
drift apart. Only the environment setup is shared, via the composite actions in
[`paolobellini/bellini.one`](https://github.com/paolobellini/bellini.one).

- `.github/workflows/analyse.yml` — on push to `main` / any PR, runs
  `composer analyse` (pint, phpstan, rector, psalm taint) and `composer ci:node`.
  The Node step carries `if: ${{ !cancelled() }}` so a PHP failure still reports
  the front-end result in the same run; the job fails either way.
- `.github/workflows/tests.yml` — on push to `main` / any PR, runs
  `composer tests` (type coverage + coverage) against a database service
  container. The engine comes from the `DB_CONNECTION`, `DB_IMAGE`, `DB_PORT`
  and `DB_OPTIONS` repository variables, defaulting to MySQL 8.0 when they are
  unset (see below).
- `.github/workflows/security.yml` — on PR targeting `main`, `staging` or `dev`,
  runs a Trivy filesystem scan failing on `HIGH,CRITICAL`. Trivy's default
  scanners (`vuln` + `secret`) both apply; vulnerabilities with no fix available
  are ignored.

`analyse.yml` and `tests.yml` use `actions/laravel/setup-app@v1.0` to install
PHP, Node, the composer/npm dependencies and the build; `security.yml` needs no
setup. Both read the toolchain versions from the repository variables
`PHP_VERSION` and `NODE_VERSION`, falling back to `8.5` and `24` when they are
not set — so a version bump is a repo setting, not an edit in two files.
Switching scan severity is still a per-project edit of the copied file.

The database engine is four repository variables — unset means MySQL 8.0:

| engine | `DB_CONNECTION` | `DB_IMAGE` | `DB_PORT` | `DB_OPTIONS` |
|---|---|---|---|---|
| MySQL | `mysql` | `mysql:8.0` | `3306` | `--health-cmd="mysqladmin ping -h 127.0.0.1" --health-interval=10s --health-timeout=5s --health-retries=5` |
| MariaDB | `mariadb` | `mariadb:11` | `3306` | `--health-cmd="healthcheck.sh --connect --innodb_initialized" --health-interval=10s --health-timeout=5s --health-retries=5` |
| PostgreSQL | `pgsql` | `postgres:17` | `5432` | `--health-cmd="pg_isready -U root" --health-interval=10s --health-timeout=5s --health-retries=5` |

### Sharding a large suite

`--sharded` swaps `tests.yml` for a variant that splits the suite across a
matrix of jobs instead of running it in one. It replaces the file rather than
adding a second one, so the suite is never run twice.

- `type-coverage` stays a single job — `--type-coverage` is static analysis,
  there is nothing to split and it needs no database.
- `tests` fans out over four shards, each job running
  `composer test -- --shard=<n>/<total>`. The total comes from
  `strategy.job-total`, so widening the matrix line is the only edit needed —
  the denominator follows on its own.

Pest balances the shards from `tests/.pest/shards.json`, a file of recorded
per-class timings. Without it, it chunks the test classes evenly and one shard
ends up dominating the wall clock; Pest warns in the run output when the file is
missing entries.

`--sharded` therefore also installs `.github/workflows/update-shards.yml`, which
regenerates it every Monday (and on demand via *Run workflow*): it runs
`composer update-shards` against the same database service and commits the file
back if it changed. Seed it once locally with `composer update-shards` so the
first sharded runs are balanced too.

The job needs `contents: write` and pushes to the default branch, so a branch
protection rule that admits no exception will reject it — either allow the
`github-actions[bot]` actor or drop the workflow and refresh the file by hand.
Note also that `--update-shards` writes nothing when the suite fails, and that
GitHub disables scheduled workflows after 60 days without repository activity.

The sharded variant drops the `--coverage --min=90` gate: each job only sees its
own slice, so a per-shard minimum is meaningless and the reports would have to be
merged to be comparable. Shard only when the suite is big enough that this is
worth losing.

Branch protection needs updating too — the required check stops being `tests`
and becomes one entry per shard (`tests (1)`, `tests (2)`, …).

`DB_OPTIONS` cannot be shared: `mysqladmin` is absent from the MariaDB image and
Postgres has neither, so the health-check is per engine. Set all four together —
a `DB_IMAGE` on its own leaves the workflow connecting with the wrong driver and
port.

## Flags

```bash
php artisan preset:install --configs --ai --scripts --github   # pick groups
php artisan preset:install --force                             # overwrite existing files / deps
php artisan preset:install --no-install                        # skip the auto composer update
```

Without flags in a non-interactive shell, all groups install.

## Conventions in brief

- **Actions pattern**: thin controllers — validate (Form Request) → bind →
  `$action->handle(...)` → Resource. One `final` action per write, single
  `handle()`.
- **No explanatory comments**; PHPDoc only (array shapes / generics).
- **Commits**: `type(scope): message`, all lowercase, subject only.
- **Tests**: Pest, ≥90% coverage. Unit tests assert the object; feature tests
  assert the database. `tests/Feature/{Model}/{Method}Test.php`.
- **PHP**: strict types, `final` classes, constructor property promotion,
  explicit return types, curly braces always.

## License

MIT
