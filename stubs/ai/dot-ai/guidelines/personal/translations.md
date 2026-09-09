# Translations

Applies only to **multilingual** applications. A single-language application
writes its strings directly (see the `attributes()` rule in
`form-requests.md`) and has none of this.

## One JSON File Per Language

Translations live in a single JSON file per language at the project root
`lang/` directory:

```
lang/it.json
lang/en.json
```

- Keyed by the source string, not by a dotted code:
  `"The briefing was saved": "Il briefing è stato salvato"`.
- Every language file holds the **same set of keys**. A key present in `it.json`
  and missing from `en.json` renders as the raw key in production.
- PHP array files under `lang/{locale}/` are used only for what Laravel itself
  expects there (`validation.php`, `auth.php`, `passwords.php`).

## Keep Them In Sync

The files are updated **in the same change** as the code that introduces a
string. Not afterwards, not in a cleanup pass.

- New user-facing string → add the key to every language file at once.
- String removed from the code → remove the key from every file.
- Wording changed → the key changes, so update it everywhere rather than
  leaving the old key orphaned.

A missing translation is not a runtime error: Laravel silently renders the key.
That is exactly why this cannot be left to a later pass — nothing will fail to
tell you.
