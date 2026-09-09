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

`php artisan preset:install` is interactive — pick any of the four groups:

### `configs` — lint / format / static analysis

Copies the configs not already in the starter kit:

| File | Tool |
|------|------|
| `pint.json` | Laravel Pint (strict types, final classes, phpdoc-only types) |
| `phpstan.neon` | Larastan level 7 + the pest-plugin-phpstan and phpstan-safe-rule extensions |
| `rector.php` | Rector + rector-laravel sets + `PestSetList::CODING_STYLE` |
| `config/essentials.php` | nunomaduro/essentials — custom overrides (`Unguard => true`, inverse of the package default) |

### `ai` — conventions

Copies the `.ai/` directory only:

- `.ai/guidelines/personal/*` — precedence, comments, commits, controllers
  (action pattern), actions, caching, enums, exceptions, form-requests,
  frontend, models, php (Safe functions), policies, query-builder, resources,
  testing, traits, translations, typescript, workflow.
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
`thecodingmachine/phpstan-safe-rule`, `spatie/laravel-typescript-transformer`.
`nunomaduro/essentials`, `spatie/laravel-data`, `spatie/laravel-query-builder`
and `thecodingmachine/safe` go into `require`. `nunomaduro/collision` and
`pestphp/pest-plugin-laravel` are **not** added — they already ship with the
starter kit.

`config.allow-plugins` gets `pestphp/pest-plugin` so the pest plugins can boot.

Composer scripts added: `lint`, `analyse`, `refactor`, `type`, `coverage`,
`tests`, `check:lint`, `check:refactor`, `php-checks`, `node-checks`,
`cleanup`.

- `composer cleanup` → Pint, Pest (90% coverage + type-coverage), PHPStan,
  Rector dry-run.

npm deps and scripts are **not** touched — the starter kit already provides
ESLint, Prettier, TypeScript and their `lint`/`format`/`types:check` scripts.

### `github` — CI workflows

First removes the starter-kit `lint.yml` + `tests.yml` (superseded), then copies
caller workflows that reference the reusable workflows / composite actions in
[`paolobellini/bellini.one`](https://github.com/paolobellini/bellini.one):

- `.github/workflows/analyse.yml` — on push to `main` / any PR, calls
  `laravel-lint.yml@v1.0` (pint + rector + phpstan + node-checks).
- `.github/workflows/tests.yml` — on push to `main` / any PR, calls
  `laravel-test.yml@v1.0`.
- `.github/workflows/security.yml` — on PR targeting `staging`, runs the
  `actions/general/security@v1.0` Trivy scan.

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
