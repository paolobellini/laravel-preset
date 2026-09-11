# Change Workflow

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

1. Run `composer analyse:static` (Pint, PHPStan, Rector dry-run) and
   `composer tests` (Pest type-coverage min 95%, Pest coverage min 90%).
2. **If anything fails or is not working, report it** — do not produce a commit
   message.
3. **If everything passes, return a commit message** following the commit
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
