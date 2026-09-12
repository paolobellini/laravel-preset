# Models

## Never Mutate Properties Directly

Never set a model attribute directly (`$user->name = '...'`). All writes go
through mass assignment — `create()`, `update()`, `fill()` — fed from the
validated DTO or attributes.

```php
// ✗ no
$user->name = $data->name;
$user->save();

// ✓ yes
$user->update($data->toArray());
```

## Property Docblocks

Every model carries a docblock declaring **every** attribute and **every**
relation as `@property-read` — always `-read`, never a writable `@property`.
Attributes are only ever read off the model; mutation happens through mass
assignment, not property assignment.

The docblock is exhaustive and is part of the model's contract:

- One line per database column — no column is omitted, including `id`,
  timestamps and soft-delete columns.
- One line per relation, fully typed: `Collection<int, Post>` for a
  has-many/belongs-to-many, `Post` or `Post|null` for a has-one/belongs-to.
- One line per accessor / computed attribute.
- Type and field name always both present, and the type matches the cast (see
  *Cast Everything*) — `CarbonImmutable` for a date cast, the enum class for an
  enum cast, `array` for a JSON cast.
- Nullable columns are `|null`. A column with a default in `$attributes` and a
  `NOT NULL` schema is **not** nullable.
- **Keep it updated.** A migration that adds, renames, removes or re-types a
  column must update the docblock in the same change. A stale docblock is a bug.

```php
/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $locale
 * @property-read UserStatus $status
 * @property-read array<int, string> $preferences
 * @property-read bool $is_admin
 * @property-read CarbonImmutable|null $email_verified_at
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read Team|null $team
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Favorite> $favorites
 */
final class User extends Authenticatable
{
    // ...
}
```

## Mass Assignment: Nothing to Declare

`nunomaduro/essentials` runs with `Unguard::class => true` (see
`config/essentials.php`), so every model is already unguarded application-wide.
Anything the model declares about mass assignment is dead code that only
contradicts that setting: no `$fillable`, no `$guarded`, no `Model::unguard()`,
and none of their attribute equivalents.

The only exception is hiding a column from serialization, and only when there is
genuinely something to hide — `password`, `remember_token`, a token or secret
column. A model with nothing to hide declares nothing.

What keeps mass assignment safe here is not `$fillable`: it is that writes are
fed from a validated DTO or the validated request payload, never from raw
`$request->all()`.

## Cast Everything

**Every** column has a cast. All of them — not just the interesting ones. `id`,
`string` columns, booleans, timestamps: no exceptions. An uncast column is an
untyped column, and the docblock type above it becomes a guess.

```php
protected function casts(): array
{
    return [
        'id' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'locale' => 'string',
        'status' => UserStatus::class,
        'preferences' => 'array',
        'is_admin' => 'boolean',
        'email_verified_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
```

## Defaults Live in `$attributes`

Every column that can have a sensible default declares it in
`protected $attributes`. This is what keeps `?? null`, `?? []`, `?? false` and
`?? 0` out of the actions: the model always hands back a usable value, so no
caller has to defend against a missing one.

```php
protected $attributes = [
    'status' => UserStatus::Pending->value,
    'preferences' => '[]',
    'is_admin' => false,
    'locale' => 'it',
];
```

- Values in `$attributes` are the **raw database** values, not the cast ones —
  an enum default is its `->value`, a JSON default is a JSON string.
- Leave out only what genuinely has no default: the primary key, foreign keys,
  timestamps, and columns the caller must always supply (`name`, `email`).
- The default must agree with the column default in the migration.
