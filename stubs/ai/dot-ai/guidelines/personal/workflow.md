# Change Workflow

After building any new feature, fix, or refactor:

1. Run `composer cleanup` (Pint, Pest type-coverage min 90%, Pest coverage
   min 90%, PHPStan, Rector dry-run).
2. **If anything fails or is not working, report it** — do not produce a commit
   message.
3. **If everything passes, return a commit message** following the commit
   conventions — subject line only, no description.
