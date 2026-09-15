# Change Workflow

## Never Act Unasked

Do what was asked. Nothing beyond it.

Noticing something that could be improved is not a reason to change it: a
duplicated block worth extracting, a missing test, a dependency behind, a file
that would read better reorganised. Mention it in one line and carry on with the
work that was asked for.

- Do not touch files the task does not require.
- Do not add a dependency, a config option, or a tool on your own initiative —
  propose it and wait, as in *Ask Before Reinventing* in the traits
  conventions.
- Do not "fix on the way" something unrelated to the request, even when the fix
  is obvious and small.
- Do not widen the scope because the wider version seems more useful.

If the request is ambiguous enough that two readings lead to different work,
ask before starting rather than picking one and building it.

## Sizing the Work

After the prompt, decide how big the change is — then say so before starting.

- **Small enough to hold in one piece** — carry it through in one go, then
  follow *After a Step*.
- **Too big for that** — split it into separate tasks, list them, and wait for
  confirmation before starting the first. Then do one task at a time, running
  *After a Step* for each, so every task is validated on its own.

The test for "too big" is whether the change can be validated in one pass. A
change that touches several layers, or that cannot be reviewed as a single diff,
is too big — split it.

If a task turns out larger than it looked once started, stop and re-split it.
Do not carry on and deliver something that cannot be reviewed.

## Building a Feature — Layer by Layer

For any big/mid feature, build **one layer at a time**, in order. Finish and
validate a layer before moving to the next.

1. **Data layer** — migrations, models, factories, seeders, plus the necessary
   unit tests. Build it whole.
2. **Service layer** — controller, actions, service, form request, resource, …
3. **Frontend / UI layer**.

The **service** and **frontend** layers are split **one feature at a time** —
a single controller method (and its actions/requests/resources/UI) per step —
so each step can be validated, committed, then move to the next.

## After a Step

After building any new feature, fix, or refactor:

1. Run `composer rector` — **not** the dry-run. The Rector config carries
   conventions written down nowhere else (strict types, type declarations, dead
   code, attribute migrations), so code that has not been through it does not
   follow them yet. Check what it changed still does what the feature needs.
2. Run `composer ai:cleanup`.
3. **If anything fails or is not working, report it** — do not produce a commit
   message.
4. **If everything passes, return a commit message** following the commit
   conventions — subject line only, no description.

Never work around a failure by weakening the tooling. Do not lower a `--min`
threshold, add a PHPStan baseline entry or `@phpstan-ignore`, exclude a file
from Pint or Rector, or disable a rule to make the run green. Fix the code, or
report the failure and stop.

## Never Disable `laravel/pao`

`laravel/pao` ships with the starter kit and reshapes the output of Pest,
PHPUnit, Paratest, PHPStan and Rector when it detects that an agent is running
the command. It is what makes those runs readable to you in the first place.

- Never set `PAO_DISABLE`, in the environment, in `.env`, in `phpunit.xml` or
  inline before a command.
- Never remove the package, and never drop its service provider or its Pest
  plugin from `composer.json`.
- Truncated or unexpected tool output is not a reason to turn it off. Read what
  it gave you, or run the underlying command again — do not change how the
  output is produced.
