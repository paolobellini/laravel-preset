# Comments

## No Explanation Comments

Do not write explanatory or inline comments. Code must be self-explanatory
through descriptive names. Inline comments are unwanted and are stripped by the
project's Pint ruleset anyway.

Incorrect:
```php
// update the user and return it
$user->update($attributes);
```

Correct:
```php
$user->update($attributes);
```

## Keep Docblocks

Keep PHPDoc docblocks — they are the only allowed documentation in code. Use
them for array shapes, generics, and type information Pint/PHPStan rely on.

```php
/**
 * @param  array<string, mixed>  $attributes
 */
public function handle(User $user, array $attributes): User
```
