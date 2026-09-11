# Commit Messages

Format: `type(scope): message`

- **type**: `feat`, `fix`, `refactor`, `chore`, ... (the kind of change).
- **scope**: a single word for what the commit touches — `(deploy)`,
  `(actions)`, `(users)`, ...
- **message**: short description of the change.
- **All lowercase.**
- **Subject line only — no body or description.**

## The Title Covers Every Uncommitted Change

The message describes **everything currently uncommitted**, not just the last
edit made. Check the working tree (`git status`, `git diff`) before writing it:
earlier changes from this session are going into the same commit, and a title
naming only the final tweak mislabels the rest.

- Several related changes → one title that names what they achieve together.
- A change that genuinely does not belong with the others → say so, and propose
  splitting the commit instead of stretching one title over both.

```
# working tree: new action, its unit test, a new form request, a renamed column

# ✗ no — names only the last thing touched
chore(users): rename the status column

# ✓ yes — covers the whole tree
feat(users): add create action with form request and status column
```

Examples:
```
feat(users): add store endpoint with action and resource
fix(deploy): correct healthcheck url
refactor(actions): extract update logic into handle method
chore(deploy): export-ignore dev files from production archive
```
