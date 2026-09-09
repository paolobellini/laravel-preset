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

1. Run `composer cleanup` (Pint, Pest type-coverage min 90%, Pest coverage
   min 90%, PHPStan, Rector dry-run).
2. **If anything fails or is not working, report it** — do not produce a commit
   message.
3. **If everything passes, return a commit message** following the commit
   conventions — subject line only, no description.
