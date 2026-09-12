# Verifying a Change with `pest --agent`

`pestphp/pest-plugin-agent` runs a one-off assertion without creating a test
file. Use it to check that an implementation you just changed actually behaves —
a route responds, a relation resolves, a job is queued, a mail fires — instead of
reasoning about it or writing a throwaway test and deleting it afterwards.

```bash
vendor/bin/pest --agent='$user = \App\Models\User::factory()->create(); expect($user->status)->toBe(\App\Enums\UserStatus::Pending);'
```

Pest wraps the snippet in `it('verify', function () { ... })`, runs it with the
project's normal configuration, and deletes the temporary file.

## It Does Not Replace the Test

This is a probe, not a test. Every rule in `testing.md` still stands: the action,
model, form request and controller you touched must have their own test files,
and mutation coverage is measured on those.

Use `--agent` while working — to confirm a change does what you think before
moving on. The regression guard is the test you write anyway.

## Single Quotes, Always

Wrap the snippet in **single** outer quotes. The shell then interprets nothing,
so `$user`, `\App\Models\User` and `!` all reach PHP literally and there is
nothing to escape. Use double quotes for PHP string literals inside.

```bash
# ✓ yes
vendor/bin/pest --agent='visit("/login")->type("email", $user->email)->press("Log in");'

# ✗ no — the shell expands $user to an empty string before PHP sees it,
#        and the check silently tests the wrong thing
vendor/bin/pest --agent="visit('/login')->type('email', $user->email);"
```

Never hand-escape `\$`. Typing it means the outer quotes are wrong.

The one character single quotes cannot hold is an apostrophe. Only then, write
the snippet to a `.php` file — body statements only, no `<?php`, no `use` — and
pass it through:

```bash
vendor/bin/pest --agent="$(cat /tmp/snippet.php)"
```

Delete that file once the check has run. It is not a test.

## Fully Qualify Every Class

The generated file has **no `use` statements**. An unqualified name throws
`Class "User" not found`.

```php
\App\Models\User::factory()->create();
\Illuminate\Support\Facades\Mail::fake();
```

## What the Snippet Inherits

From `tests/Pest.php` it gets the classes and traits bound with
`pest()->extend(...)->use(...)->in(...)` — so `TestCase` and `RefreshDatabase`
are already applied.

It does **not** get:

- `beforeEach` / `afterEach` hooks or groups attached to a directory. If the
  setup you need lives in one, inline it at the top of the snippet.
- Traits added inline — they only come through `tests/Pest.php`.

Other things that bite:

- `__DIR__` and `__FILE__` resolve to the temporary directory, not `tests/`. Use
  `base_path()` / `storage_path()`, or an absolute path.
- Seed the state you need inside the snippet with factories. The test database
  starts empty on every run.
- One focused snippet per invocation. Several `--agent` options run, but every
  failure is reported as `verify` and they become impossible to tell apart.

## Browser Checks Need Another Package

`visit()`, screenshots and interaction flows come from
`pestphp/pest-plugin-browser`, which pulls in Node and downloads Playwright
browser binaries.

If it is missing, **ask before installing it** — same rule as any other
dependency (see `traits.md`). Never run the install off your own initiative.

## Never Paper Over a Failure

If a check fails because a factory, a seeder or a migration is missing, stop and
say so. Do not bend the snippet into a fixture that makes the failure disappear,
and do not change the test configuration to get a green result — the same rule
as in `workflow.md`.
