# Actions

An action is one unit of business logic. Almost everything that *does*
something is an action — a controller, a listener, a console command, a job or
another action all call the same class.

## Context-Free

An action knows nothing about HTTP. That is what makes it reusable everywhere.

- Never type-hint `Request` / a Form Request, never call `request()`,
  `auth()`, `session()`, `abort()` or any response helper inside an action.
- Anything from the request arrives as an argument: the acting user is a
  `User` parameter, not `auth()->user()`.
- The action returns a model or a value, never a `Response`, a redirect or a
  resource.

```php
// ✗ no — bound to HTTP, unusable from a command or a listener
public function handle(StoreUserRequest $request): User
{
    return User::create($request->validated());
}

// ✓ yes — callable from anywhere
public function handle(CreateUserData $data): User
{
    return User::create($data->toArray());
}
```

## Naming

The name is the action performed, nothing more: `CreateUser`, `ListUsers`,
`UpdateUser`, `DestroyUser`, `RestoreUser`, `SendWelcomeEmail`. Verb first,
subject after, singular or plural as the operation dictates.

**No `Action` suffix.** The class lives in `app/Actions/`, so the namespace
already says what it is — `App\Actions\CreateUserAction` says it twice.

Write the file by hand in `app/Actions/`. Do **not** use
`php artisan make:action`: it appends the `Action` suffix itself
(`make:action CreateUser` produces `CreateUserAction`), and the suffix cannot be
removed by publishing the stub because it is applied by the command, not by the
template.

```php
namespace App\Actions;

final readonly class CreateUser
{
    public function handle(CreateUserData $data): User
    {
        return User::create($data->toArray());
    }
}
```

## Shape

- `final readonly` — always.
- One public method, `handle()`.
- **Avoid private methods.** An action that needs helpers is doing more than
  one thing: extract the second thing into its own action and call it.
- One action does essentially **one operation**, but it may call other actions
  when a step is genuinely a separate unit of work.

## Input

- **Up to 3–4 fields** → pass them as direct typed arguments. No DTO for two
  scalars.
- **More than that** → a `Data` object from `spatie/laravel-data`, never a loose
  `array`. Do not document an `array{...}` shape in PHPDoc; model it.
- The bound model is a parameter on update/destroy actions.

```php
// few fields — direct arguments
public function handle(User $user, Role $role): User

// a real payload — a Data object
public function handle(CreateUserData $data): User
```

## Transactions

- **A single database write needs no transaction.** One `create()`, one
  `update()`, one `delete()` is already atomic on its own.
- **Two or more writes** are wrapped in `DB::transaction()`, so a failure
  halfway leaves nothing behind.

```php
public function handle(CreateOrderData $data): Order
{
    return DB::transaction(function () use ($data): Order {
        $order = Order::create($data->except('lines')->toArray());
        $order->lines()->createMany($data->lines);

        return $order;
    });
}
```

Two things to get right, both of which cause real outages:

- **Never nest transactions.** When an action wrapped in a transaction calls
  another action that also wraps, the inner one is a savepoint, not a
  transaction — a rollback there does not behave the way the code reads. Decide
  which action owns the transaction (the outermost one) and leave the inner
  actions bare.
- **Nothing slow inside the transaction.** No HTTP call, no mail, no file
  upload, no queue-less job, no external API. Every row touched stays locked
  until the transaction closes, and a two-second API call is a two-second lock.
  Do the slow work before the transaction, or dispatch it after the commit.

```php
// ✗ no — the row stays locked for the whole API round-trip
DB::transaction(function () use ($data): void {
    $user = User::create($data->toArray());
    Http::post('https://crm.example/contacts', $user->toArray());
});

// ✓ yes — commit first, then the slow work
$user = DB::transaction(fn (): User => User::create($data->toArray()));

SyncContactToCrm::dispatch($user);
```

## Tests

Every action has a unit test covering the business logic it implements — happy
path **and** sad path. See the *Action Unit Tests* section in `testing.md`.
