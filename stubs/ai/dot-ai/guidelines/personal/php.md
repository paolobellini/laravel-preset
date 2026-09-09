# PHP

## Safe Functions

The project requires [`thecodingmachine/safe`](https://github.com/thecodingmachine/safe).
Use the Safe version of any PHP core function it wraps, never the native one.

Native PHP functions signal failure by returning `false` (or `null`, or `-1`),
which silently poisons the value downstream. The Safe wrappers throw a
`Safe\Exceptions\*Exception` instead, so a failure surfaces where it happens and
the return type is always the useful one.

- Import the function, never call it fully qualified inline:
  `use function Safe\json_decode;`
- Never write `@` error suppression, and never check the return against `false`
  for a function Safe covers — let the exception propagate, or catch the
  specific `Safe\Exceptions\*Exception`.
- The signature is identical to the native one, so only the import changes.

```php
use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\preg_match;

// ❌ false-returning core functions
$raw = file_get_contents($path);
if ($raw === false) {
    throw new RuntimeException("Cannot read {$path}");
}
$data = json_decode($raw, true);

// ✅ Safe — throws on failure, return type is never false
$data = json_decode(file_get_contents($path), true);
```

Catch only when there is something to do about it:

```php
use function Safe\file_get_contents;
use Safe\Exceptions\FilesystemException;

try {
    $raw = file_get_contents($path);
} catch (FilesystemException) {
    return null; // an absent config file is not an error here
}
```

Functions Safe does not wrap (because they cannot fail, or don't use `false` to
report failure — `count`, `array_map`, `str_replace`, …) stay as-is: there is no
`Safe\` version to import.
