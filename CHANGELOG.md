# Changelog

All notable changes to `paolobellini/laravel-preset` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **scripts** — `spatie/laravel-typescript-transformer` moved from
  `require-dev` to `require`. `php artisan typescript:install` publishes
  `app/Providers/TypeScriptTransformerServiceProvider.php`, which extends a
  package class and is registered in `bootstrap/providers.php`, so the
  application failed to boot after `composer install --no-dev`.
- **scripts** — a runtime dependency (`nunomaduro/essentials`,
  `spatie/laravel-data`, `spatie/laravel-query-builder`,
  `spatie/laravel-typescript-transformer`, `thecodingmachine/safe`) already
  listed in `require-dev` is no longer skipped: it is required again, which
  moves it to `require`.
- **scripts** — `sail artisan preset:install` failed with *Docker is not
  running*: the command, already inside the container, called
  `./vendor/bin/sail composer require`, and Sail found no Docker there. With
  `LARAVEL_SAIL` set it now calls `composer` directly.
- **codegraph** — setting CodeGraph up never finished: the group ran the
  `npx @colbymchenry/codegraph` wizard, whose questions could not be answered
  through a piped process. The binary is now installed with `npm install
  --global @colbymchenry/codegraph`, and every run wires the project with
  `codegraph install --yes --target claude --location local` (`.mcp.json`,
  `.claude/settings.json`, `.claude/CLAUDE.md` — nothing global) before
  building the index with `codegraph init --yes`. Inside the Sail container the
  group stops with a notice instead of looking for a binary that lives on the
  host.
- **lefthook** — the `prettier` and `eslint` jobs ran `npx` on the host while
  the PHP jobs ran in the Sail container, so a hook could use a different Node
  than `sail composer ci:node` (or fail with none installed). Both are now
  prefixed with `vendor/bin/sail`, stripped like the others when Sail is not
  detected.
- **lefthook** — the `stan` job matched every staged `*.php`, handing PHPStan
  files outside the `paths` of `phpstan.neon` (`rector.php`, migrations,
  `public/index.php`, …) and failing commits CI would pass. Its `glob` and
  `exclude` now mirror `paths` and `excludePaths`.

## [1.1.0] - 2026-09-15

### Changed

- **ai** — expanded the `.ai/` personal guidelines:
  - `controllers.md` — read methods (`index`, search, filters) must validate
    with a Form Request and read input only via `->validated('field')`, using
    `when()` to gate filter scopes; local filters as model `#[Scope]` scopes and
    global filters as global scopes; real, meaningful validation rules
    (`min`/`max` matching columns, specific rules — no bare `string`); actions
    pass 1–2 fields directly, otherwise a DTO (`app/DTOs/`, `final readonly`,
    `fromRequest()` constructor) instead of a loose array.
  - `models.md` (new) — never mutate model attributes directly (mass assignment
    only); all `@property` docblock lines are `@property-read`.
  - `workflow.md` — build features layer by layer (data → service → frontend),
    splitting service and frontend one controller method at a time.
  - `testing.md` — model unit tests must assert the model keys via
    `array_keys($model->toArray())`.

- **ai** — `workflow.md` gained two sections ahead of the existing layer split.
  *Never Act Unasked* holds the agent to the request: no unrelated file touched,
  no dependency or tool added on its own initiative, no improvement made in
  passing because it happened to be noticed, and a question asked up front when
  two readings of the request lead to different work. *Sizing the Work* has the
  agent judge the change after the prompt and say so before starting — carried
  through in one pass when it can be validated in one pass, otherwise split into
  listed tasks that wait for confirmation, with a re-split if a task turns out
  bigger than it looked.

- **github** — `analyse.yml` gates `composer ci:node` behind
  `dorny/paths-filter`: the step is skipped when a pull request touches no
  front-end file, which is the normal shape of a Composer dependency bump. The
  filter is expressed as `**` minus the PHP side, the Markdown docs and the
  agent conventions (`.ai`, `.claude`, `.junie`, `.factory`) rather than as a
  list of front-end paths, so the failure mode is running the checks
  unnecessarily rather than skipping them by mistake. `analyse` itself still runs on every
  PR — PHPStan checks the project's call sites against the new vendor
  signatures, which is exactly what a bump can break.
- **github** — the Renovate schedules were widened after the upstream advice to
  allow at least three to four hours: `* 4-6 * * 1` became `* 0-6 * * 1`, and
  `lockFileMaintenance` moved from three hours on the first calendar day of the
  month to `* * 1-7 * 1` — the first Monday, a full day wide. A schedule is a
  permission window, not a trigger, so too narrow a window silently skips the
  run. Dropped the redundant `dependencyDashboard`, already enabled by
  `config:recommended`.
- **github** — `analyse.yml` and `tests.yml` no longer call the
  `paolobellini/bellini.one` reusable workflows; the jobs are inlined and invoke
  the preset's own composer scripts (`analyse` + `ci:node`, `tests`). The
  reusable workflows referenced script names (`php-checks`, `node-checks`) that
  the preset writes into each project's `composer.json`, so a rename here broke
  CI in every project installed from an older tag, and no single tag could be
  correct for all of them. Only `actions/laravel/setup-app@v1.0` — which names no
  script — is still shared.
- **github** — `tests.yml` no longer hardcodes MySQL: the service container
  reads the `DB_CONNECTION`, `DB_IMAGE`, `DB_PORT` and `DB_OPTIONS` repository
  variables and falls back to MySQL 8.0 when they are unset — the same four
  knobs the reusable workflow exposed as `inputs`. The README lists the ready
  value sets for MySQL, MariaDB and PostgreSQL.
- **github** — `analyse.yml` and `tests.yml` read `php-version` / `node-version`
  from the `PHP_VERSION` / `NODE_VERSION` repository variables, defaulting to
  `8.5` / `24`, so both workflows always agree on the toolchain.
- **github** — `security.yml` inlines the Trivy filesystem scan instead of
  calling `actions/general/security@v1.0`, so severity and scanners are editable
  per project. It also triggers on PRs targeting `main`, `staging` or `dev`
  instead of `staging` only.
- **scripts** — dependencies are no longer pinned in the preset. `preset:install`
  now adds them with `composer require` (no version constraint), so every install
  resolves the newest stable release instead of the constraint that happened to be
  current when the preset was released. `--no-install` switches to
  `composer require --no-update`: the resolved constraints land in `composer.json`
  without installing. `--force` re-requires packages already present, bumping them
  to the latest. Sail is still detected and used as the composer wrapper.
- **scripts** — `config.allow-plugins` gets `pestphp/pest-plugin` so the pest
  plugins can boot on a project that doesn't allow it yet.
- **configs** — `phpstan.neon` includes
  `vendor/pestphp/pest-plugin-phpstan/extension.neon` and adds `tests/` to the
  analysed paths (the extension analyses the test suite).
- **configs** — `rector.php` adds `PestSetList::CODING_STYLE`.
- **ai** — `testing.md` gained a *Mutation Testing* rule: `covers()` belongs to
  feature tests only, and may list only controllers, actions and policies.
- **ai** — `testing.md` — *Model Unit Tests*: the test must exist and stay in
  sync; fixed order of keys test first, then one test per non-obvious cast
  (enums, custom casts — never the ordinary ones), then at least one test per
  remaining model method. New *Test Names* rule: `it(...)` describing the
  observable outcome, never `handles` (nor *works* / *behaves* / *manages*).
- **ai** — `models.md` — the `@property-read` docblock is exhaustive (every
  column, every relation fully typed, every accessor) and must be updated by
  the migration that changes a column; **every** column has a cast, even the
  obvious ones; `protected $attributes` declares a default for every column that
  can have one, so actions never need `?? null` / `?? []`; nothing is
  declared about mass assignment (`$fillable` / `$guarded` / `Model::unguard()`
  and their Laravel 13 attributes `#[Fillable]` / `#[Guarded]` / `#[Unguarded]`
  are all redundant under Essentials' `Unguard`); hiding a column from
  serialization uses the `#[Hidden(...)]` class attribute, never the legacy
  `protected $hidden`.

### Added

- **codegraph** — a seventh, **opt-in** group (`--codegraph`) running
  `codegraph init` to build the project's code graph. When the binary is absent
  the group offers to run `npx @colbymchenry/codegraph` and does nothing if you
  decline; a non-interactive run only prints the command. The installer is
  offered rather than assumed because it also writes MCP config into every agent
  it detects, which is per-machine state the preset has no business changing
  silently. An existing `.codegraph/` is left alone without `--force`.

- **skills** — a sixth, **opt-in** group (`--skills`, offered but never
  pre-selected) running the `skills` CLI into `.agents/skills`, with symlinks
  for Claude Code. From `jpcaparas/superpowers-laravel`:
  `laravel:queues-and-horizon`, `laravel:http-client-resilience` and
  `laravel:performance-select-columns`, covering ground the personal guidelines
  leave open; skills that would compete with an existing convention are
  deliberately excluded. From `mattpocock/skills`: `wait-what` and `teach`,
  user-invoked only. The command spells each skill as its own `--skill` flag
  (`--skill=name` is silently ignored and installs all of them) and names the
  target agents, so the CLI cannot recreate the agent directories the `ai` group
  removes.

- **ai** — installing the group now deletes the agent scaffolding
  `boost:install` wrote for everything but Claude Code (`.amp`, `.codex`,
  `.cursor`, `.factory`, `.gemini`, `.grok`, `.junie`, `.kiro`, `.pi`, `.zed`
  and `.github/copilot-instructions.md`) and pins `boost.json` to
  `agents: ["claude_code"]`. Deleting alone would not hold: Boost re-detects
  agents whenever `boost.json` names none, and detection is satisfied by an
  `.idea` directory or a PhpStorm install, so the directories would come back on
  the next run. `.idea` and `.vscode` are left alone — Boost writes into them,
  but they belong to the editor.
- **github** — `.github/trivy.yaml` and an empty `.vex/openvex.json`. Trivy
  reads the VEX document itself and drops findings marked `not_affected` or
  `fixed`, so the scanner gates directly and the triage lives in git, reviewed
  in a pull request. OpenVEX rather than the CycloneDX VEX Dependency-Track
  exports: Trivy accepts CycloneDX VEX only when scanning an SBOM, and
  Dependency-Track's exporter omits the component list for that variant, so
  every `affects[].ref` points at the application instead of the vulnerable
  package. `ignore-unfixed` gates the scan, and a second report-only pass lists
  the vulnerabilities with no released fix rather than hiding them. `.vex/**`
  joins the paths excluded from the front-end checks. The document's `@id` is
  derived from the project's composer name (`urn:vex:vendor:project`) and
  stamped with the install time, since the spec requires each document to carry
  an IRI of its own.
- **github** — `--renovate` installs `.github/renovate.json` and removes
  `.github/dependabot.yml`, so a project runs one update bot, not two. The
  config mirrors the Dependabot grouping and cooldown, and adds
  `lockFileMaintenance` (monthly full lockfile refresh, transitive dependencies
  included — Dependabot only touches those when they are vulnerable) and the
  dependency dashboard. `vulnerabilityAlerts` resets `minimumReleaseAge` to
  `null` so security fixes are never held back. Validated against
  `renovate-schema.json`.
- **github** — `--renovate-selfhosted` adds `.github/workflows/renovate.yml`,
  running Renovate from `renovatebot/github-action` every three hours for
  projects where the GitHub App cannot be installed. It implies `--renovate`.
  The job reads a `RENOVATE_TOKEN` secret rather than `GITHUB_TOKEN`, because
  pull requests opened with the default token do not trigger workflows and the
  update PRs would never be tested. Its cron covers exactly the window the
  config allows (`0 22,23 * * 0` and `0 0-6 * * 1`, the two DST projections of
  Monday 00:00–06:00 in `Europe/Rome`) — nine runs a week instead of the
  fifty-six a three-hourly cron would bill for while doing nothing.
- **github** — `.github/dependabot.yml`: weekly Composer, npm and GitHub Actions
  updates, with minor/patch bumps grouped per ecosystem and per
  production/development split and majors left as individual PRs. Commit
  prefixes match the repo convention (`chore(deps)`, `chore(deps-dev)`,
  `chore(ci)`), and a `cooldown` delays each release by 3 days (patch), 7
  (minor) or 14 (major) before it is proposed — the GitHub Actions ecosystem
  takes a flat 7, being the one that does not support the SemVer-specific keys.
  Validated against the SchemaStore `dependabot-2.0` schema.
- **github** — `--sharded` installs a matrix variant of `tests.yml` that splits
  the suite across four jobs, each running
  `composer test -- --shard=<n>/${{ strategy.job-total }}`; the denominator comes
  from `strategy.job-total`, so the matrix line is the single place to widen it.
  Type coverage stays a single job. `--sharded` also installs
  `update-shards.yml`, a weekly (and manually dispatchable) job that runs
  `composer update-shards` against the same database service and commits
  `tests/.pest/shards.json` back when the timings changed, so the shards stay
  balanced as the suite grows. It overwrites `tests.yml` instead of sitting next to it, so the
  suite cannot run twice, and its stub lives outside `stubs/github/` so the
  directory copy never picks it up by accident. The variant trades away the
  coverage minimum, which cannot be enforced per shard.
- **lefthook** — a fifth, **opt-in** group (`--lefthook`, offered but never
  pre-selected in the prompt) copying `lefthook.yml`: a parallel `pre-commit`
  running pint, phpstan and the test suite on the staged PHP files plus prettier
  and eslint on the staged front-end files, and a `pre-push` running
  `composer rector:test:dry` and `composer test:mutate`. The commands are
  prefixed with `vendor/bin/sail` only when Sail is detected in the
  project; otherwise the prefix is stripped from the copied file. Lefthook has
  no composer package — install the binary and run `lefthook install` once.
- **scripts** — `tia` composer script (`pest --tia`), re-running only the tests
  affected by the change.
- **scripts** — `update-shards` now clears the config cache and runs in parallel
  like the other test scripts, instead of a bare `pest --update-shards`.
- **scripts** — `vimeo/psalm` to `require-dev` and a `taint` composer script
  (`psalm --taint-analysis --no-cache`). Psalm is scoped to taint analysis only —
  PHPStan/Larastan remains the static analyser.
- **configs** — `tests/Pest.php` is created from a stub when the project has
  none, and otherwise patched: `pest()->tia()->locally()` is appended to the
  existing file, leaving its own setup untouched, and skipped entirely when TIA
  is already configured. Test impact analysis stays local, so the commit hook
  can afford a coverage run while CI keeps measuring the full suite.
- **configs** — `rector-tests.php`: Rector for `tests/` (`LARAVEL_TESTING` +
  `PestSetList::CODING_STYLE`). `rector.php` is now scoped to `app/` and
  `database/`, so the two configs no longer process the test suite twice.
- **configs** — `psalm.xml`, scoped to `app/` and `routes/` at `errorLevel="8"`
  so a taint run reports tainted input rather than a second opinion on typing.
- **scripts** — `mutate` composer script (`pest --mutate --covered-only
  --parallel`), prefixed with `Composer\Config::disableProcessTimeout` so a long
  run is not killed at Composer's 300s process timeout.
- **scripts** — the scripts are layered: leaf (one tool), groups
  (`analyse:static`, `analyse`, `tests`, `ci:node`) and entry points
  (`pre-commit`, `pre-push`, `ci:php`, `ci`). Entry points compose groups and
  never re-list a leaf script, so a new tool is declared in one place. CI and
  `pre-push` are disjoint: CI owns the application contract, `pre-push` owns
  test-suite quality (`test:mutate` and `rector:test:dry`), and nothing runs
  twice. New `test` leaf script (`pest --parallel`) for the commit hook, which
  needs the suite without a coverage gate.
- **lefthook** — `pre-commit` is now scoped to `{staged_files}` with a per-job
  `glob`; Pint, Prettier and ESLint fix and re-stage (`stage_fixed`) instead of
  failing; and a `pre-push` hook runs the mutation score and rector over
  `tests/`.
- **ai** — `workflow.md` — the post-step check is `composer analyse:static` +
  `composer tests`; a failure is never worked around by weakening the tooling
  (no lowered `--min`, no baseline, no `@phpstan-ignore`, no excluded file); and
  `laravel/pao` is never disabled (`PAO_DISABLE`, provider or plugin removal).
- **ai** — `pest-agent.md` (new) — `vendor/bin/pest --agent='<php>'` runs a
  one-off assertion with no test file, for checking that a change behaves while
  working on it. Covers the single-outer-quotes rule (double quotes expand
  `$user` to nothing and silently check the wrong thing), fully qualified class
  names (the generated file has no `use`), what the snippet inherits from
  `tests/Pest.php` and what it does not (`beforeEach` hooks, groups, inline
  traits), and the browser plugin needing explicit permission before install. It
  never replaces the test files `testing.md` requires.
- **ai** — `commits.md` — the commit title must cover every uncommitted change
  in the working tree, not just the last edit; unrelated work is proposed as a
  separate commit instead.
- **scripts** — every script that boots the application (`tia`, `type`,
  `coverage`, `mutate`) now runs
  `@php artisan config:clear --ansi @no_additional_args` first: a cached config
  silently overrides `config/` and `.env`, and `@no_additional_args` (Composer
  2.8+) keeps user-passed options from being appended to `config:clear`. The
  lint/analysis scripts are untouched — they do not boot the app.
- **scripts** — `pestphp/pest-plugin-rector`, `pestphp/pest-plugin-phpstan`,
  `pestphp/pest-plugin-evals`, `pestphp/pest-plugin-agent`,
  `pestphp/pest-plugin-faker` and `pestphp/pest-plugin-mutate` to `require-dev`.
- **scripts** — `thecodingmachine/phpstan-safe-rule` to `require-dev`, included
  in `phpstan.neon`, so calling a native function that has a `Safe\` equivalent
  fails static analysis.
- **scripts** — `spatie/laravel-data` to `require` (DTOs, see
  `.ai/guidelines/personal/controllers.md`).
- **scripts** — `thecodingmachine/safe` to `require`.
- **scripts** — `spatie/laravel-query-builder` to `require` and
  `spatie/laravel-typescript-transformer` to `require-dev`.
- **ai** — `query-builder.md` (new) — `QueryBuilder` only in `index` / listing
  reads, never in writes, actions, models or `show`; everything allow-listed
  with explicit `AllowedFilter::` factories; `defaultSort()` always; and the
  Form Request stays mandatory, validating the nested query parameters
  (`filter.*`, `sort`, `include`, `page`) because the package reads the query
  string straight off the request.
- **ai** — `typescript.md` (new) — frontend types are generated with
  `php artisan typescript:transform`, never hand-written; `#[TypeScript]` goes
  on `Data` objects and enums, never on an Eloquent model (magic attributes are
  not introspectable) — a model reaches TypeScript through a `Data` object that
  mirrors its docblock and casts.
- **ai** — `controllers.md` — the *Standard Read Method* section now builds the
  listing query with `QueryBuilder` instead of a chain of `when()` calls, and
  *Validation Rules* points at `form-requests.md` instead of duplicating it.
- **ai** — `actions.md` (new) — actions are context-free (no `Request`, no
  `auth()`, no response helpers), named for the operation with no `Action`
  suffix (`CreateUser`, `ListUsers`) and written by hand in `app/Actions/`
  (`php artisan make:action` is not used: it appends the suffix itself),
  `final readonly`, a single `handle()`, private helpers avoided, one operation each but free to call other
  actions; up to 3–4 fields are passed as direct arguments, anything larger as a
  `spatie/laravel-data` object; a single write needs no transaction, two or
  more are wrapped — never nested, and never
  around slow work that would hold the row lock.
- **ai** — `controllers.md` — *Action* and *Data Objects* now point at
  `actions.md`; DTOs are `spatie/laravel-data` classes built from the validated
  payload (`Data::from($request->validated())`) instead of hand-rolled
  `final readonly` classes with a `fromRequest()` constructor.
- **ai** — `testing.md` — *Action Unit Tests*: happy and sad path, lean, asserted
  on the returned object. Feature tests must cover both paths too, and their
  names read as a user story rather than naming the method under test.
- **ai** — `enums.md` (new) — every backed enum exposes `label()` via an
  exhaustive `match ($this)` with no `default`; the derivable helpers
  (`options()`, `values()`) live in the `HasLabel` concern at
  `app/Enums/Concerns/`; cases reach the frontend through a single generic
  `EnumResource` returning exactly `value` + `label`.
- **ai** — `policies.md` (new) — one policy method per controller method, the
  authenticated user always first, `bool` returns, and the Form Request
  delegating to it; policies get **no** unit test — the controller's feature
  test must prove both the granted and the refused case (`403` with the database
  unchanged).
- **ai** — `caching.md` (new) — never cache on your own initiative, propose it
  and wait; `Cache::flexible()` with an explicit fresh/stale window is the
  default over `remember()`; invalidation runs through a model observer
  (`created` / `updated` / `deleted`, plus `restored` when soft-deletable)
  attached with `#[ObservedBy]`, never a `Cache::forget()` scattered at the call
  site — with the caveat that bulk query-builder writes bypass observers.
- **ai** — `frontend.md` (new) — with Inertia, URLs come from `laravel/wayfinder`
  helpers instead of hand-written strings; no interface or type is declared
  inside a page or component — they live in `resources/js/types/{domain}.ts` and
  re-export the generated ones.
- **ai** — `translations.md` (new) — multilingual applications keep one JSON file
  per language (`lang/it.json`), same key set across all of them, updated in the
  same change as the code that introduces the string.
- **ai** — `form-requests.md` — a rule carrying business logic becomes a `final`
  `ValidationRule` class in `app/Rules/`, request-agnostic and reusable, with its
  own unit test.
- **ai** — `resources.md` — every resource has a simple unit test asserting the
  exact list of returned keys with `toBe()`.
- **ai** — `resources.md` (new) — every resource declares the model it wraps in
  a `@property-read $resource` docblock and binds it to a local variable at the
  top of `toArray()`, so fields are real typed property accesses instead of
  `JsonResource::__get()` magic that static analysis cannot see; recurring
  structures become nested resources (`new XResource(...)` /
  `XResource::collection(...)`) rather than inlined arrays.
- **ai** — `enums.md` — the `EnumResource` example follows the new resource
  convention.
- **ai** — `traits.md` (new) — a method written a second time moves into a trait
  under a `Concerns/` directory; before writing one for a generic concern
  (normalisation, slugs, phone numbers, VAT IDs, money, …) propose two or three
  well-known, maintained packages and wait for the author to choose — never
  `composer require` on the strength of your own suggestion; every trait
  has a unit test with one test per public method, exercised through an
  anonymous class.
- **ai** — `exceptions.md` (new) — a recognised failure gets a `final` exception
  in `app/Exceptions/`, thrown through a named constructor so the wording lives
  in one place; variants of the same failure are extra methods on that class,
  not extra classes; exceptions carry no HTTP concern and are never swallowed.
- **ai** — `testing.md` — added the Traits row to *What to Test Where* and an
  *Enum & Trait Unit Tests* section.
- **ai** — `form-requests.md` (new) — named `{Method}{Model}Request` after the
  controller method it validates (`IndexUserRequest`, `StoreUserRequest`,
  `UpdateUserRequest`, `DestroyUserRequest`), one per method and never shared
  between `store` and `update`; `authorize()` and `rules()` always
  declared, `attributes()` when field names would surface raw; rules carry real
  bounds (a name is `min:2`, `max:150`, not the column's `max:255`; numbers are
  always bounded); `attributes()` is written in the application language when
  single-language, and goes through `__()` only when the app is multilingual.
- **ai** — `testing.md` — *Form Request Unit Tests*: one test for `authorize()`,
  a dataset-driven `with()` test over a few meaningful rules, and one test for
  `attributes()` when declared. The unit/feature split is now explicit in both
  directions: a unit test never touches the database, a feature test never
  asserts on the returned model instance, and **no** unit test declares
  `covers()`.
- **ai** — `php.md` (new) — always use the `Safe\` wrapper of a core function
  instead of the native one, so failures throw instead of returning `false`;
  no `@` suppression, no `=== false` checks, import with
  `use function Safe\...`.

### Removed

- **scripts** — `barryvdh/laravel-ide-helper` and the `ide-helper` composer
  script.

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

[1.1.0]: https://github.com/paolobellini/laravel-preset/releases/tag/v1.1.0
[1.0.0]: https://github.com/paolobellini/laravel-preset/releases/tag/v1.0.0
