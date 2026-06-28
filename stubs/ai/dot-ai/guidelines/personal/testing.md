# Testing

## Coverage

- Minimum **90%** code coverage with Pest. Enforced by `composer cleanup`
  (`pest --coverage --min=90` and `pest --type-coverage --min=90`).
- Every new feature, fix, or refactor must keep coverage at or above 90%.

## What to Test Where

| Code | Test type | Location | Asserts against |
|------|-----------|----------|-----------------|
| Models | Unit | `tests/Unit/Models/{Model}Test.php` | the model/object directly |
| Enums | Unit | `tests/Unit/Enums/{Enum}Test.php` | the enum directly |
| Actions | Unit | `tests/Unit/Actions/{Action}Test.php` | the returned model directly |
| Controller methods | Feature | `tests/Feature/{Model}/{Method}Test.php` | the database |

- **Unit tests assert directly on the model/object** — test computed
  attributes, casts, relationships, scopes, enum cases/methods, and action
  return values by inspecting the object itself.
- **Feature tests assert the database** — hit the route/controller and assert
  with `assertDatabaseHas`, `assertDatabaseMissing`, `assertDatabaseCount`,
  plus the HTTP response.

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
