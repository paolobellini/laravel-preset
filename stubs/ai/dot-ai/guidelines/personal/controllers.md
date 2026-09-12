# Controllers & Actions

Use the **action pattern** for every write operation. Controllers stay thin:
validate -> bind -> delegate to action -> return resource. No business logic in
the controller.

## Standard Write Method

A standard controller method is composed of:

1. A **Form Request** that validates the request.
2. **Route model binding** (when a model is needed).
3. An **Action** invoked with `$action->handle(...)` (see `actions.md`).
4. A **Resource** for the response.

```php
public function update(UpdateUserRequest $request, User $user, UpdateUser $action): UserResource
{
    $user = $action->handle($user, UpdateUserData::from($request->validated()));

    return new UserResource($user);
}
```

- Pass a **`Data` object** to the action (built from the validated payload), or
  direct arguments when the action needs only a few fields.
- The action receives the bound model on update/destroy.

## Action

See `actions.md` — naming (`CreateUser`, no `Action` suffix), `final readonly`,
a single `handle()`, no knowledge of HTTP, and when to wrap in a transaction.

From the controller's side: pass **1–3/4 fields as direct arguments**, or a
`Data` object when the payload is bigger. Never a loose `array`.

```php
public function store(StoreUserRequest $request, CreateUser $action): UserResource
{
    $user = $action->handle(CreateUserData::from($request->validated()));

    return new UserResource($user);
}
```

## Data Objects

- Built with `spatie/laravel-data`: `final` classes extending
  `Spatie\LaravelData\Data`, one per action input, in `app/DTOs/`.
- Typed promoted properties model the exact shape — this replaces the
  `array{...}` PHPDoc.
- Build them from the **validated** payload — `Data::from($request->validated())`,
  never `Data::from($request)`, so unvalidated input cannot reach the action.
- Export them to the frontend with `#[TypeScript]` (see `typescript.md`).

```php
namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?int $age = null,
    ) {}
}
```

Read operations may return a resource directly without an action.

## Standard Read Method (index, search, filters)

The `index` method, and any `GET` method that accepts user input (filters,
search, sorting, …), **must** validate the request with a **Form Request**.

Listing methods build the query with `spatie/laravel-query-builder` — see
`query-builder.md` for the allow-list rules and for how the query parameters
are validated.

```php
public function index(IndexUserRequest $request): AnonymousResourceCollection
{
    $users = QueryBuilder::for(User::class)
        ->allowedFilters([
            AllowedFilter::partial('name'),
            AllowedFilter::scope('role'),
        ])
        ->allowedSorts(['name', 'created_at'])
        ->defaultSort('-created_at')
        ->paginate()
        ->withQueryString();

    return UserResource::collection($users);
}
```

- The Form Request is type-hinted even though `QueryBuilder` reads the query
  string itself — that is what makes validation run first.
- For a `GET` method that is **not** a listing (no filters, no sorting), read
  input only through `$request->validated('field')` — never `$request->input()`
  or `$request->query()` directly.

## Filters

- **Local filters** live in the model as **local scopes**. Keep filter logic out
  of the controller.

- **Global filters** that always apply (e.g. scoping every query by `user_id`)
  are implemented as a **global scope**, not repeated per query.

```php
final class OwnedByUser implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('user_id', auth()->id());
    }
}
```

## Validation Rules

See `form-requests.md` — `authorize()` / `rules()` / `attributes()`, and the
standard for meaningful rules (`min` and `max` that match the real data, not
the column length).
