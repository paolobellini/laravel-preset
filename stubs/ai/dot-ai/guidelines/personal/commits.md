# Commit Messages

Format: `type(scope): message`

- **type**: `feat`, `fix`, `refactor`, `chore`, ... (the kind of change).
- **scope**: a single word for what the commit touches — `(deploy)`,
  `(actions)`, `(users)`, ...
- **message**: short description of the change.
- **All lowercase.**
- **Subject line only — no body or description.**

Examples:
```
feat(users): add store endpoint with action and resource
fix(deploy): correct healthcheck url
refactor(actions): extract update logic into handle method
chore(deploy): export-ignore dev files from production archive
```
