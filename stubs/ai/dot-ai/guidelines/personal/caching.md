# Caching

Cache is for data that does not need to be perfectly fresh — dashboard tiles,
statistics, counters, aggregations, expensive reports.

## Ask First

**Never introduce a cache on your own initiative.** When a read looks like a
candidate, say so, explain what would be cached and with which window, and
**wait for confirmation**. A stale number nobody expected is worse than a slow
query somebody accepted.

Never cache: anything a user is about to edit, authorisation decisions,
anything holding personal data keyed per user without that being the explicit
intent.

## Use `flexible()`

The default is `Cache::flexible()`, not `remember()`. It takes two windows —
fresh and stale:

```php
Cache::flexible('dashboard:stats', [5 * 60, 30 * 60], fn (): array => [
    'briefings' => Briefing::count(),
    'pending' => Briefing::pending()->count(),
]);
```

- Within the **first** window the cached value is returned as-is.
- Between the first and the second it is still returned immediately, and the
  value is refreshed in the background — so no user ever waits for the
  recomputation.
- After the second window the cache is cold and the value is recomputed inline.

Pick windows that match how the data actually moves: minutes for a dashboard,
longer for a report nobody watches change. Say what you chose and why, so the
window is a decision and not a leftover.

`remember()` is the exception — use it only when a stale-while-revalidate read
is genuinely wrong for the case.

## Flushing With Observers

Invalidation is **automatic**, driven by the model, never a `Cache::forget()`
scattered at the call site — a forget written next to one write is a forget
missing from every other write.

```php
final class BriefingObserver
{
    public function created(Briefing $briefing): void
    {
        $this->flush();
    }

    public function updated(Briefing $briefing): void
    {
        $this->flush();
    }

    public function deleted(Briefing $briefing): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        Cache::forget('dashboard:stats');
    }
}
```

Attach it with the attribute on the model:

```php
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(BriefingObserver::class)]
final class Briefing extends Model
```

- Cover `created`, `updated` and `deleted` — plus `restored` when the model is
  soft-deletable.
- Keep the cache keys in one place, so the observer and the reader cannot
  disagree about the string.
- Observers fire on Eloquent events only. A mass `update()` on a query builder
  bypasses them — when a bulk write exists, flush explicitly there too and say
  so.
