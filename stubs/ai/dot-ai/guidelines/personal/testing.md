# Testing

## Coverage

- Minimum **90%** code coverage with Pest. Enforced by `composer tests`
  (`pest --coverage --min=90` and `pest --type-coverage --min=90`).
- Every new feature, fix, or refactor must keep coverage at or above 90%.

## What to Test Where

| Code | Test type | Location | Asserts against |
|------|-----------|----------|-----------------|
| Models | Unit | `tests/Unit/Models/{Model}Test.php` | the model/object directly |
| Enums | Unit | `tests/Unit/Enums/{Enum}Test.php` | the enum directly |
| Traits | Unit | `tests/Unit/Concerns/{Trait}Test.php` | an anonymous class using it |
| Validation rules | Unit | `tests/Unit/Rules/{Rule}Test.php` | the rule directly |
| Resources | Unit | `tests/Unit/Resources/{Resource}Test.php` | the returned array |
| Policies | — | none — covered by the feature test | — |
| Actions | Unit | `tests/Unit/Actions/{Action}Test.php` | the returned model directly |
| Form Requests | Unit | `tests/Unit/Requests/{Request}Test.php` | the request object directly |
| Controller methods | Feature | `tests/Feature/{Model}/{Method}Test.php` | the database |

- **Unit tests assert directly on the model/object** — test computed
  attributes, casts, relationships, scopes, enum cases/methods, and action
  return values by inspecting the object itself. A unit test never asserts
  against the database: no `assertDatabaseHas`, no `assertDatabaseCount`.
- **Feature tests assert the database** — hit the route/controller and assert
  with `assertDatabaseHas`, `assertDatabaseMissing`, `assertDatabaseCount`,
  plus the HTTP response. A feature test never asserts on the returned model
  instance: the row in the database is the proof, not the object in memory.

### Model Unit Tests

Every model has a unit test at `tests/Unit/Models/{Model}Test.php`. It must
exist and must stay in sync with the model — a new column, cast, relation or
method updates this file in the same change.

Fixed order, and nothing else:

1. **Keys first.** The first test always asserts the model's keys — that
   `toArray()` exposes exactly the expected attributes, in order:

   ```php
   it('exposes the expected attributes', function () {
       $user = User::factory()->create();

       expect(array_keys($user->toArray()))->toBe([
           'id',
           'name',
           'email',
           'status',
           'preferences',
           'is_admin',
           'email_verified_at',
           'created_at',
           'updated_at',
       ]);
   });
   ```

2. **Only the non-obvious casts.** One test per cast that turns the value into
   something else — enums above all, plus custom casts and value objects. Do
   **not** test the ordinary casts: no test for `'string'`, `'integer'`,
   `'boolean'` or a date cast. They are guaranteed by the framework and the keys
   test already proves the column is exposed.

   ```php
   it('casts status to the UserStatus enum', function () {
       $user = User::factory()->create(['status' => UserStatus::Active->value]);

       expect($user->status)->toBe(UserStatus::Active);
   });
   ```

3. **At least one test per remaining method.** Every other method on the
   model — accessor, computed attribute, scope, relation, helper — gets at
   least one test. No method is left uncovered.

### Action Unit Tests

Every action has a unit test at `tests/Unit/Actions/{Action}Test.php` that
covers the **business logic it implements** — both paths:

- **Happy path** — the action does what it exists to do, asserted on the
  returned model/value.
- **Sad path** — every way it is meant to fail: an exception thrown, a
  guard refusing, an invalid state rejected. An action with no sad path to test
  is rare; if there truly is none, that is a deliberate finding, not an
  omission.

Keep them lean. One arrangement, one call, one focused assertion — no fixture
scaffolding for a class whose whole job is a handful of lines.

```php
it('creates a user with the pending status', function () {
    $user = (new CreateUser())->handle(new CreateUserData(
        name: 'Ada',
        email: 'ada@example.com',
    ));

    expect($user->name)->toBe('Ada')
        ->and($user->status)->toBe(UserStatus::Pending);
});

it('refuses to create a user with an email already taken', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    expect(fn () => (new CreateUser())->handle(new CreateUserData(
        name: 'Ada',
        email: 'ada@example.com',
    )))->toThrow(UserAlreadyExists::class);
});
```

Assertions stay on the returned object — the database belongs to the feature
test (see *What to Test Where*).

### Enum & Trait Unit Tests

- **Enums** — one test covering `label()` for every case (see `enums.md`), plus
  a test for any other method the enum declares.
- **Traits** — one test per public method, exercised through a small anonymous
  class rather than a real consumer (see `traits.md`). Every method is covered;
  the tests stay one-call-one-assertion simple.

### Custom Validation Rule & Resource Unit Tests

- **Custom validation rules** — the passing case and each failing case, driven
  by a `with()` dataset (see `form-requests.md`).
- **Resources** — one test asserting the returned structure: the exact list of
  keys, with `toBe()` so an accidentally exposed field fails (see
  `resources.md`).

### Form Request Unit Tests

Every Form Request has a unit test at `tests/Unit/Requests/{Request}Test.php`.
Three things, nothing more:

1. **`authorize()`** — one test, whatever it returns.

   ```php
   it('authorizes any user', function () {
       expect((new StoreUserRequest())->authorize())->toBeTrue();
   });
   ```

2. **A few validation rules**, driven by a dataset with `with()` — not one test
   per rule. Cover the meaningful bounds, not every field.

   ```php
   it('rejects an invalid value', function (string $field, mixed $value) {
       $validator = Validator::make(
           [...$this->validPayload, $field => $value],
           (new StoreUserRequest())->rules(),
       );

       expect($validator->fails())->toBeTrue()
           ->and($validator->errors()->has($field))->toBeTrue();
   })->with([
       'name too short' => ['first_name', 'a'],
       'name too long' => ['first_name', str_repeat('a', 151)],
       'malformed email' => ['email', 'not-an-email'],
       'age below the minimum' => ['age', 17],
       'unknown status' => ['status', 'nope'],
   ]);
   ```

3. **`attributes()`**, when the request declares it — one simple test.

   ```php
   it('names the attributes in Italian', function () {
       expect((new StoreUserRequest())->attributes())
           ->toMatchArray(['first_name' => 'nome']);
   });
   ```

See `form-requests.md` for what the request itself must contain.

## Feature Test Structure

Group feature tests in a folder named after the model (singular: `User`,
`Employee`, `Post`). One file per controller method.

```
tests/Feature/User/StoreTest.php
tests/Feature/User/UpdateTest.php
tests/Feature/User/DestroyTest.php
tests/Feature/User/{Method}Test.php
```

`Store` / `Update` / `Destroy` map to the resource methods; any custom
controller method uses `{Method}Test.php`.

Each file covers **both paths**:

- **Happy path** — the request succeeds and the database reflects it.
- **Sad path** — validation rejected, unauthorized, model missing, conflicting
  state. A feature test file with only the happy path is incomplete.
- **Authorisation, both ways** — when the method is guarded by a policy, the
  allowed user succeeds and the denied user gets a `403` with the database
  unchanged. Policies have no unit test of their own; this is where they are
  proven (see `policies.md`).

The test name reads as a **user story**: who does what, and what they get back.
Not the name of the method under test.

```php
// ✗ no — describes the code
it('tests the store endpoint');
it('returns 422');

// ✓ yes — describes the user
it('lets an admin create a user and lands them in the listing');
it('stops a guest from creating a user and keeps the table empty');
it('rejects a registration with an email that is already taken');
```

## Mutation Testing

Mutation testing runs **only on feature tests**.

**No unit test ever declares `covers()`** — not a model test, not an action
test, not a Form Request test. Unit tests assert on the object, not on the
behaviour that mutations are meant to break.

In a feature test, `covers()` accepts **only** controllers, actions and
policies. Nothing else — no models, enums, DTOs, form requests, jobs, events,
observers, services or helpers.

```php
covers(StoreUserController::class, CreateUser::class, UserPolicy::class);

it('stores a user', function () {
    // ...
});
```

- One `covers()` per feature test file, at the top, listing only the
  controller / action / policy the file exercises.
- A class outside those three kinds must never appear in `covers()`, even when
  the test does hit it.

## Test Names

Describe the behaviour with `it(...)`, reading as a sentence: `it('...')`.

Never use the word **handles** — it says nothing about what the code does.
Same for other empty verbs: *works*, *behaves*, *manages*, *deals with*.

```php
// ✗ no
it('handles an invalid email');
it('handles the empty case');
it('works with soft deleted users');

// ✓ yes
it('rejects an invalid email with a 422');
it('returns an empty collection when the team has no members');
it('excludes soft deleted users from the listing');
```

State the observable outcome, and the condition that produces it.
