# Resources

A resource is the typed shape of what leaves the application. It never reads
attributes off `$this` by magic.

## Type the Underlying Model

Every resource declares the model it wraps in a `@property-read` docblock, and
`toArray()` opens by binding it to a local variable:

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Briefing $resource
 */
final class BriefingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $briefing = $this->resource;

        return [
            'id' => $briefing->id,
            'title' => $briefing->title,
            'status' => $briefing->status->value,
            'created_at' => $briefing->created_at,
        ];
    }
}
```

Why both lines are mandatory:

- **The docblock** is what makes `$this->resource` typed. Without it a resource
  is an untyped bag: PHPStan cannot check a single field, and the model's
  `@property-read` docblock (see `models.md`) buys nothing here.
- **The local variable** is what keeps it typed in use. `$this->title` goes
  through `JsonResource::__get()` — magic that resolves at runtime, is invisible
  to static analysis, and silently returns `null` for a typo. `$briefing->title`
  is a real property access on a real type: a renamed column fails the analysis
  instead of shipping a `null` to the frontend.

Never mix the two styles in one resource. Once `$briefing` exists, everything
goes through it.

## Naming and Location

- `app/Http/Resources/`, named `{Model}Resource` — `BriefingResource`,
  `UserResource`.
- `final`, like every other class.
- A distinct shape of the same model gets its own resource
  (`BriefingListItemResource` next to `BriefingResource`), rather than
  conditionals inside one.

## Nested Resources

When a structure repeats — the same author block, the same address, the same
enum pair in several payloads — it becomes its own resource and is nested,
never re-inlined:

```php
public function toArray(Request $request): array
{
    $briefing = $this->resource;

    return [
        'id' => $briefing->id,
        'title' => $briefing->title,
        'mode' => new EnumResource($briefing->mode),
        'author' => new UserResource($briefing->author),
        'attachments' => AttachmentResource::collection($briefing->attachments),
    ];
}
```

- A single model → `new XResource($model)`; a collection →
  `XResource::collection($models)`.
- Enum cases go through `EnumResource` (see `enums.md`), never as a hand-built
  `['value' => …, 'label' => …]` array.
- Nest only what actually repeats. A one-off block of three fields stays
  inline; extracting it just adds a file to open.
- Guard against N+1: a nested relation must be eager-loaded by the query that
  produced the model.

## Tests

Every resource has a simple unit test at `tests/Unit/Resources/{Resource}Test.php`
asserting the **structure it returns** — the keys, and that they are the only
ones.

```php
it('exposes the briefing structure', function () {
    $briefing = Briefing::factory()->create();

    $payload = (new BriefingResource($briefing))->toArray(request());

    expect(array_keys($payload))->toBe(['id', 'title', 'status', 'created_at'])
        ->and($payload['id'])->toBe($briefing->id);
});
```

- Assert the keys with `toBe()`, not `toHaveKeys()` — an accidentally exposed
  field is exactly what this test exists to catch.
- One test per resource is enough; it is a shape check, not a behaviour suite.
- Nested resources are asserted in their own test, not re-asserted through the
  parent.
