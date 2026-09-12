# TypeScript Types

Frontend types are **generated**, never hand-written. `spatie/laravel-typescript-transformer`
derives them from the PHP source, so a shape can only ever be defined once.

Never hand-write an interface in `.ts` / `.vue` that mirrors a PHP class. If a
type describes data coming from the backend, it is generated. A hand-written
duplicate silently drifts the moment the PHP changes.

## What Gets Exported

Mark the class with `#[TypeScript]` from
`Spatie\TypeScriptTransformer\Attributes\TypeScript`:

- **Data objects** (`spatie/laravel-data`) — the shape of everything sent to or
  received from the frontend.
- **Enums** — every backing enum used in a payload.

```php
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public UserStatus $status,
        public ?CarbonImmutable $email_verified_at,
    ) {}
}
```

## Models Are Exported Through a Data Object

An Eloquent model is **not** annotated with `#[TypeScript]`. Its attributes are
magic — there are no typed public properties to read — so the transformer has
nothing to introspect and would emit an empty or wrong type.

The model's shape reaches TypeScript through a `Data` object that mirrors it:

- One `Data` class per model shape exposed to the frontend
  (`UserData`, `UserListItemData`, …), built with `Data::from($user)`.
- Its typed properties are the single source of truth for the generated type,
  and they must match the model's `@property-read` docblock and casts (see
  `models.md`).
- Add a column to the model → update the docblock, the cast and the `Data`
  object in the same change, then regenerate.

## Generating

```bash
php artisan typescript:transform          # regenerate
php artisan typescript:transform --watch  # while developing
```

- Regenerate whenever a `Data` object or an exported enum changes, and commit
  the generated file — `composer ci:node` runs `npm run types:check`
  against it, so a stale file fails CI.
- The generated file is written by the configured writer (by default a single
  `generated.d.ts`). Never edit it by hand.
- Setup lives in `app/Providers/TypeScriptTransformerServiceProvider.php`
  (created by `php artisan typescript:install`), not in a config file. The
  laravel-data integration is registered there with
  `$config->extension(new LaravelDataTypeScriptTransformerExtension());`.

## Typing Escape Hatches

Use these only when the PHP type cannot express the TypeScript one:

- `#[TypeScriptType('array<int, string>')]` — a PHPStan-style type, transpiled.
- `#[LiteralTypeScriptType('`user_${string}`')]` — raw TypeScript, emitted
  verbatim. Last resort.
- `#[Optional]` — the property is optional in the generated type.
- `#[Hidden]` — the property is left out of the generated type.

Reach for a real PHP type first; an escape hatch is a type the compiler can no
longer check for you.
