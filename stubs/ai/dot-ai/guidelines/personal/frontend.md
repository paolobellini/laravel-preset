# Frontend

Applies to the Inertia + Vue + TypeScript side of the application.

## Routes: Use Wayfinder

When `laravel/wayfinder` is installed, URLs come from the generated helpers,
never from a hand-written string or a `route('...')` name typed by hand.

```ts
import { update } from '@/actions/App/Http/Controllers/BriefingController'

router.put(update(briefing.id).url, form)
```

- A renamed route or a changed URI breaks the build instead of producing a 404
  at runtime.
- Applies to every call site: `router.*`, `<Link :href>`, form actions, fetches.
- Regenerate the helpers when routes change, and commit them.

Without Inertia — or without Wayfinder in the project — fall back to named
routes, never to hardcoded paths.

## Types Live in `resources/js/types/`

**No interface or type is declared inside a page or a component.** Types go in
a dedicated file per domain:

```
resources/js/types/user.ts
resources/js/types/briefing.ts
```

```ts
// ✗ no — resources/js/pages/Briefings/Index.vue
interface Briefing {
  id: number
  title: string
}

// ✓ yes — resources/js/types/briefing.ts
export interface BriefingFilters {
  status?: BriefingStatus
  search?: string
}
```

- A type describing backend data is **generated**, not written — see
  `typescript.md`. If `spatie/laravel-typescript-transformer` is not installed,
  write it by hand, once, under `types/`. The file under `types/` re-exports or composes the generated
  types; it does not restate them by hand.
- Only frontend-only shapes (component props, local UI state, filter objects)
  are hand-written there.
- A page imports its types; it never declares them. Two pages needing the same
  shape is the normal case, and a shape declared inside one of them is the
  shape the other will duplicate.
