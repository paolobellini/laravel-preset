# Traits

When the same method starts appearing in more than one class — normalisation,
formatting, a shared computed value, a repeated query helper — it stops being
copied and becomes a trait.

## When to Extract

- **Two occurrences is the trigger.** The second time a method is written, it
  moves into a trait and both classes `use` it. There is no third copy.
- **Check for a package first**, and propose one. See below.
- The trait holds **behaviour**, not state. A trait that needs its own
  properties to work is a collaborator class wearing a disguise.

## Ask Before Reinventing

When the shared behaviour is something generic — string normalisation,
slugging, phone numbers, VAT/tax IDs, IBANs, money and currency, date
recurrence, country or language lists — a maintained package almost certainly
does it better than a hand-written trait would.

Before writing that trait:

1. Check whether the project already depends on something that covers it.
2. **Propose two or three concrete packages**, named, with one line each on what
   it does and why it fits. Well-known and dependable only: actively
   maintained, widely used, compatible with the project's PHP and Laravel
   versions. No abandoned one-star repositories, no "this looks like it might
   work".
3. Say plainly when a package is overkill and a five-line trait is the better
   answer — that is a valid proposal too.

Then **stop and wait**. The choice is the author's: confirm one, reject them
all, or name a different package. Never run `composer require` on the strength
of your own suggestion.

Write the trait only once it is settled that no package should be used.

## Shape

- One concern per trait, named for what it provides: `HasLabel`,
  `NormalisesPhoneNumbers`, `FormatsMoney`.
- Located next to what it serves, under a `Concerns/` directory:
  `app/Enums/Concerns/`, `app/Models/Concerns/`, `app/Actions/Concerns/`.
- Methods are typed exactly as anywhere else — no `mixed` in, no `mixed` out.

```php
namespace App\Models\Concerns;

trait NormalisesPhoneNumbers
{
    public function normalisePhoneNumber(string $number): string
    {
        // ...
    }
}
```

## Tests

**Every trait has a unit test**, at `tests/Unit/Concerns/{Trait}Test.php`, with
**one test per public method**. Simple tests — one call, one assertion — but
none of the methods is left untested. A trait is code that runs in several
classes at once; an untested method there is a bug multiplied by its users.

Exercise the trait through a small anonymous class, so the test covers the
trait and not one particular consumer:

```php
it('strips spaces and punctuation from a phone number', function () {
    $subject = new class {
        use NormalisesPhoneNumbers;
    };

    expect($subject->normalisePhoneNumber('+39 02 1234-567'))->toBe('+39021234567');
});
```

Same rules as every other unit test: assertions on the object, no database, no
`covers()` (see `testing.md`).
