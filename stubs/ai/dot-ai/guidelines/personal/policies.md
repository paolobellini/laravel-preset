# Policies

Authorisation for controller operations lives in a policy. A controller never
decides for itself who may act.

## One Method Per Controller Method

The policy mirrors the controller: one method per operation it guards, named
after it.

| Controller method | Policy method |
|-------------------|---------------|
| `index` | `viewAny(User $user)` |
| `show` | `view(User $user, Briefing $briefing)` |
| `store` | `create(User $user)` |
| `update` | `update(User $user, Briefing $briefing)` |
| `destroy` | `delete(User $user, Briefing $briefing)` |
| any custom `{method}` | `{method}(User $user, ...)` |

- The first argument is always the **authenticated user**; the bound model
  follows when the operation targets one.
- Every method returns `bool`. The rule lives here — not duplicated in the
  Form Request, not re-checked in the action.
- `app/Policies/{Model}Policy.php`, `final`.

```php
final class BriefingPolicy
{
    public function update(User $user, Briefing $briefing): bool
    {
        return $user->is($briefing->author);
    }
}
```

The Form Request delegates to it rather than restating the rule (see
`form-requests.md`):

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('briefing'));
}
```

## Tests

**A policy has no unit test.** Testing `update()` in isolation only proves that
a boolean expression returns a boolean; it says nothing about whether the route
is actually protected.

Instead, the feature test for the controller method **must** cover both sides
of the authorisation:

- the allowed user performs the operation and the database changes;
- the denied user is refused (`403`) and the database is unchanged.

```php
it('lets the author update their own briefing', function () {
    $briefing = Briefing::factory()->for($this->user, 'author')->create();

    $this->actingAs($this->user)
        ->put(route('briefings.update', $briefing), ['title' => 'Rivisto'])
        ->assertRedirect();

    $this->assertDatabaseHas('briefings', ['id' => $briefing->id, 'title' => 'Rivisto']);
});

it('stops a user from updating a briefing written by someone else', function () {
    $briefing = Briefing::factory()->create();

    $this->actingAs($this->user)
        ->put(route('briefings.update', $briefing), ['title' => 'Rivisto'])
        ->assertForbidden();

    $this->assertDatabaseMissing('briefings', ['id' => $briefing->id, 'title' => 'Rivisto']);
});
```

A feature test file for a guarded method without its refusal case is
incomplete — the guard is exactly the part that silently stops working.
