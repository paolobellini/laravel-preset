# Form Requests

Every controller method that accepts input has a Form Request. It always
declares `authorize()` and `rules()`, and declares `attributes()` when the
field names would otherwise surface raw in an error message.

## Naming

The class is named after the **controller method it validates**, then the
model: `{Method}{Model}Request`.

| Controller method | Form Request |
|-------------------|--------------|
| `index` | `IndexUserRequest` |
| `store` | `StoreUserRequest` |
| `update` | `UpdateUserRequest` |
| `destroy` | `DestroyUserRequest` |
| `restore` | `RestoreUserRequest` |
| any custom `{method}` | `{Method}UserRequest` |

- The model is singular, exactly as the model class (`User`, `Employee`).
- One Form Request per controller method — never one shared between `store` and
  `update`, even when the rules start out identical. They diverge (`unique`
  ignoring the current record, fields required on create and optional on
  update), and a shared class turns that into conditionals.
- The name mirrors the feature test file for the same method
  (`tests/Feature/User/StoreTest.php` → `StoreUserRequest`), so the three
  pieces of a method are found by the same word.

## `authorize()`

Always present, never omitted — even when the answer is trivially `true`.
An explicit `return true;` states that the check was considered; a missing
method leaves the reader guessing.

```php
public function authorize(): bool
{
    return true;
}
```

When the request maps to a policy, delegate instead of re-implementing the rule:

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('user'));
}
```

## `rules()`

Rules are **real and meaningful**. A bare `string`, `integer` or `nullable`
validates nothing — it only says what the type is, which the cast already says.

- Every string carries `min` **and** `max`, matching the real column and the
  real data. A person's first or last name is `min:2`, `max:150` — not
  `max:255` copied from the column length out of habit.
- Every number carries `min` and `max` bounded by what the value can actually
  be: an age is `min:18`, `max:120`; a quantity is `min:1`, `max:999`; a
  percentage is `min:0`, `max:100`. Never an unbounded `integer`.
- Use the specific rule when one exists: `email`, `url`, `uuid`, `date`,
  `Rule::enum()`, `Rule::in()`, `exists:`, `unique:`, `confirmed`, `image`,
  `mimes:`, `after:`, `before:`.
- Always state `required` or `nullable` explicitly, never neither.
- Dates get a real boundary — `before:today`, `after:start_date` — not a lone
  `date`.

```php
public function rules(): array
{
    return [
        'first_name' => ['required', 'string', 'min:2', 'max:150'],
        'last_name' => ['required', 'string', 'min:2', 'max:150'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'age' => ['nullable', 'integer', 'min:18', 'max:120'],
        'status' => ['required', Rule::enum(UserStatus::class)],
        'born_at' => ['nullable', 'date', 'before:today'],
    ];
}
```

## Custom Validation Rules

When a rule carries **business logic** — something more than a format check,
and something a second Form Request could need — it does not live as a closure
inside `rules()`. It becomes a rule class in `app/Rules/`, `final`, implementing
`ValidationRule`.

```php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class VatNumberIsRegistered implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isRegistered($value)) {
            $fail('La partita IVA non risulta registrata.');
        }
    }
}
```

```php
'vat_number' => ['required', 'string', 'size:11', new VatNumberIsRegistered()],
```

- The trigger is **reuse or logic**, not length. A one-line closure used once
  stays a closure; a rule expressing a domain decision becomes a class the first
  time it is written, because the second caller is what makes it drift.
- The class states one rule. A class checking two unrelated things is two rules.
- It has no knowledge of the request — same reason as actions: it must be
  usable from any Form Request.
- **Every custom rule has a unit test** at `tests/Unit/Rules/{Rule}Test.php` —
  the passing case and each failing case, driven by a `with()` dataset. See
  `testing.md`.

## `attributes()`

Declares the human-readable name of each field, so the error message reads
`Il nome è obbligatorio` instead of `Il first_name è obbligatorio`.

- **Single-language application** — write the names directly in the
  application's language (Italian). No translation layer for a language that
  will never change.

  ```php
  public function attributes(): array
  {
      return [
          'first_name' => 'nome',
          'last_name' => 'cognome',
          'born_at' => 'data di nascita',
      ];
  }
  ```

- **Multilingual application** — and only then — the names go through
  translations:

  ```php
  public function attributes(): array
  {
      return [
          'first_name' => __('validation.attributes.first_name'),
          'last_name' => __('validation.attributes.last_name'),
      ];
  }
  ```

Pick one based on what the application actually is. Do not add `__()` calls
"in case it becomes multilingual later".

## Tests

Every Form Request has its own unit test — see the *Form Request Unit Tests*
section in `testing.md`.
