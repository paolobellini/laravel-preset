# Exceptions

A failure the application knows about gets a **dedicated exception class**.
Throwing a bare `RuntimeException`, or letting a generic one escape, loses the
one thing that matters: what actually went wrong.

## Create the Exception

When a piece of code can fail in a way the application is meant to recognise —
and there is no dedicated exception for it yet — create one. It lives in
`app/Exceptions/`, is `final`, and is named after the failure:
`UserAlreadyExists`, `PaymentDeclined`, `ImportFileUnreadable`.

```php
namespace App\Exceptions;

use RuntimeException;

final class UserAlreadyExists extends RuntimeException
{
    public static function withEmail(string $email): self
    {
        return new self("A user with the email {$email} already exists.");
    }
}
```

Throw it through the named constructor, never with `new` plus a message string
written at the call site:

```php
throw UserAlreadyExists::withEmail($data->email);
```

That keeps the wording in one place, so the same failure never reads two
different ways in two different logs.

## One Exception, Several Cases

When the same failure has variants, they are **methods on the same exception**,
not separate classes. The class is the *kind* of failure; the methods are the
*cases*.

```php
final class ImportFailed extends RuntimeException
{
    public static function fileUnreadable(string $path): self
    {
        return new self("The import file {$path} could not be read.");
    }

    public static function unexpectedColumns(int $expected, int $found): self
    {
        return new self("The import file has {$found} columns, expected {$expected}.");
    }

    public static function emptyFile(string $path): self
    {
        return new self("The import file {$path} contains no rows.");
    }
}
```

Split into a second class only when a caller genuinely needs to `catch` one
variant and not the others.

## Where They Are Thrown

- Actions throw them; that is the sad path their unit test asserts (see
  `testing.md`).
- The exception carries no HTTP concern — no status code, no response. Rendering
  is the application's exception handler's job, not the exception's.
- Never catch and swallow. Catch only where there is a real fallback, and catch
  the specific class, never `\Exception`.
