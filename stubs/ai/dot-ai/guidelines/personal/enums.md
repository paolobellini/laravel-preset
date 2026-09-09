# Enums

Every backed enum exposes a human-readable **label**. The enum owns its wording;
no controller, resource or Blade view re-maps a case to a string.

## `label()`

Each enum implements `label()` with a `match ($this)` covering every case —
exhaustively, so adding a case is a compile-time error and not a silent blank.

```php
namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PrintMode: string
{
    use HasLabel;

    case BlackWhite = 'black_white';
    case Color = 'color';

    public function label(): string
    {
        return match ($this) {
            self::BlackWhite => 'Black and white',
            self::Color => 'Colour',
        };
    }
}
```

- No `default` arm in the `match`. A default silently swallows a new case.
- The label is the display string, in the application's language (see the
  `attributes()` rule in `form-requests.md` for the single-language vs
  multilingual choice).

## The `HasLabel` Concern

`label()` itself cannot live in the trait — the wording is data specific to each
enum. What the trait carries is everything **derivable** from it, so no enum
rewrites the same helpers.

The trait lives in `app/Enums/Concerns/HasLabel.php`:

```php
namespace App\Enums\Concerns;

trait HasLabel
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromLabel(string $label): self
    {
        // ...
    }
}
```

Every enum that has a label `use`s it. Add a helper here the moment a second
enum needs it — never copy it into both.

## `EnumResource`

When enum cases are sent to the frontend — to fill a select, a filter, a badge
legend — they go through the dedicated `EnumResource`, never as a bare string
or a hand-built array.

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read BackedEnum&HasLabelContract $resource
 */
final class EnumResource extends JsonResource
{
    /**
     * @return array{value: string|int, label: string}
     */
    public function toArray(Request $request): array
    {
        $case = $this->resource;

        return [
            'value' => $case->value,
            'label' => $case->label(),
        ];
    }
}
```

```php
return EnumResource::collection(PrintMode::cases());
```

- One resource for every enum — `EnumResource` is generic, there is no
  `PrintModeResource`.
- The payload is always exactly `value` + `label`. A frontend that needs more
  than those two is asking for something that is not an enum.
- It follows the resource rules like any other — typed `@property-read` and a
  local variable in `toArray()`, see `resources.md`.

## Tests

Every enum has a unit test — see *What to Test Where* in `testing.md`. It
covers `label()` for every case, and the trait's helpers are tested once on the
trait itself (see `traits.md`).
